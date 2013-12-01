<?php
/**
 * Admin class to query the ZCS api for domain related requests.
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 */
namespace Jlaso\ZimbraSoapApiBundle\Service\Admin;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Admin;
use Jlaso\ZimbraSoapApiBundle\Entity\Domain;
use Jlaso\ZimbraSoapApiBundle\Entity\DomainAlias;
use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Entity;
use Jlaso\ZimbraSoapApiBundle\Service\ZCS\SoapClient;

class DomainAdmin extends Admin
{
    /**
     * Fetches all domains from the soap webservice and returns them as an array
     * containing Domain objects
     *
     * @return Domain[]
     */
    public function getDomains()
    {
        $domains = $this->soapClient->request('GetAllDomainsRequest')->children()->GetAllDomainsResponse->children();
        $results = array();
        foreach ($domains as $domain) {
            $results[] = Domain::createFromXml($domain);
        }

        return $results;
    }

    /**
     * Fetches a single domain from the webservice and returns it
     * as a Domain object
     *
     * @param string $domain
     *
     * @return Domain
     */
    public function getDomain($domain)
    {
        $params = array(
            'domain' => array(
                '_'  => $domain,
                'by' => 'id'
            )
        );

        $response = $this->soapClient->request('GetDomainRequest', $params);
        $domains = $response->children()->GetDomainResponse->children();

        return Domain::createFromXml($domains[0]);
    }

    /**
     * Fetches a list of all domain aliasses defined in the system
     * Note that in order to properly link an alias to a domain the zimbraDomainAliasTargetId
     * property must be set in zimbra, else there is no failsafe way of determining what aliasses
     * belong to what domain
     *
     * @return DomainAlias[]
     */
    public function getAllDomainAliasses()
    {
        $attributes = array(
            'applyCos' => 1,
            'types' => 'domains'
        );
        $params = array(
            'query' => '(zimbraDomainType=alias)'
        );

        $response = $this->soapClient->request('SearchDirectoryRequest', $attributes, $params);
        $aliasList = $response->children()->SearchDirectoryResponse->children();

        $results = array();
        foreach ($aliasList as $aliasXml) {
            /** @var $alias DomainAlias */
            $alias = DomainAlias::createFromXml($aliasXml);
            $targetId = $alias->getTargetid();
            if(!$targetId){
                // Todo: log that we have an alias without target id
                continue;
            }
            $domainEntity = $this->getDomain($targetId);
            $alias->setTargetname($domainEntity->getName());
            $results[] = $alias;
        }

        return $results;
    }

    /**
     * Get all aliasses for a given domain which is identified by it's Zimbra ID
     * 
     * @param string|Domain $domain The zimbra id of the domain you are retrieving aliasses for or an instance of the Domain Entity
     *                              
     * @throws \InvalidArgumentException
     * 
     * @return DomainAlias[]
     */
    public function getDomainAliasses($domain)
    {
        if($domain instanceof Domain){
            $domainId = $domain->getId();
        } elseif(is_string($domain)) {
            $domainId = $domain;
        } else {
            throw new \InvalidArgumentException(__METHOD__ . ' only accepts the ID of a domain or a Domain Entity');
        }

        $attributes = array(
            'applyCos' => 1,
            'types' => 'domains'
        );
        $params = array(
            'query' => sprintf('(&amp;(zimbraDomainType=alias)(zimbraDomainAliasTargetId=%s))', $domainId)
        );

        $response = $this->soapClient->request('SearchDirectoryRequest', $attributes, $params);
        $aliasList = $response->children()->SearchDirectoryResponse->children();

        $results = array();
        foreach ($aliasList as $aliasXml) {
            /** @var $alias DomainAlias */
            $alias = DomainAlias::createFromXml($aliasXml);
            $targetId = $alias->getTargetid();
            if(!$targetId){
                // Todo: log that we have an alias without target id
                continue;
            }
            $domainEntity = $this->getDomain($targetId);
            $alias->setTargetname($domainEntity->getName());
            $results[] = $alias;
        }

        return $results;
    }

    /**
     * Creates a new DomainAlias with name $alias for a given domain
     * 
     * @param string|Domain $domain
     * @param string                           $alias
     * @param null|string                      $description Optionally a description for the item in Zimbra
     *                                                      
     * @return DomainAlias
     */
    public function createDomainAlias($domain, $alias, $description = null)
    {
        if(is_string($domain)) {
            $domain = $this->getDomain($domain);
        }

        $properties = array(
            'name'       => $alias,
            'attributes' => array(
                'zimbraDomainType' => 'alias',
                'zimbraMailCatchAllForwardingAddress' => "@".$domain->getName(),
                'zimbraMailCatchAllAddress' => "@".$domain->getName(),
                'zimbraDomainAliasTargetId' => $domain->getId(),
                'description' => $description ?: 'domain alias of ' . $domain->getName()
            )
        );

        $response = $this->soapClient->request('CreateDomainRequest', array(), $properties);
        $domainXmlResponse = $response->children()->CreateDomainResponse->children();

        $alias = DomainAlias::createFromXml($domainXmlResponse[0]);
        $alias->setTargetname($domain->getName());

        return $alias;
    }

    /**
     * Removes all aliasses from a domain
     *
     * @param string|DomainAlias $domain
     *
     * @throws \InvalidArgumentException
     * 
     * @return bool
     */
    public function deleteAllDomainAliasses($domain)
    {
        if($domain instanceof Domain){
            $domainId = $domain->getId();
        } elseif(is_string($domain)) {
            $domainId = $domain;
            $domain = $this->getDomain($domainId);
        } else {
            throw new \InvalidArgumentException(__METHOD__ . ' only accepts the ID of a domain or a Domain Entity');
        }

        $aliasses = $this->getDomainAliasses($domain);
        foreach($aliasses as $alias){
            $this->deleteDomainAlias($alias);
        }

        return true;
    }

    /**
     * Removes a domain alias from the ZCS webservice
     * 
     * @param string|DomainAlias $domainAlias
     * 
     * @return bool
     */
    public function deleteDomainAlias($domainAlias)
    {
        return $this->deleteDomain($domainAlias, false, false);
    }

    /**
     * Removes all accounts from a domain
     * 
     * @param string|DomainAlias $domain
     *
     * @throws \InvalidArgumentException
     * 
     * @return bool
     */
    public function deleteAllDomainAccounts($domain)
    {
        if($domain instanceof Domain){
            $domainId = $domain->getId();
        } elseif(is_string($domain)) {
            $domainId = $domain;
            $domain = $this->getDomain($domainId);
        } else {
            throw new \InvalidArgumentException(__METHOD__ . ' only accepts the ID of a domain or a Domain Entity');
        }

        $accountAdmin = new AccountAdmin($this->getSoapClient());
        $accounts = $accountAdmin->getAccountListByDomain($domain->getName());
        foreach($accounts as $account) {
            $accountAdmin->deleteAccount($account->getId());
        }

        return true;
    }

    /**
     * Creates a domain in the ZCS soap webservice
     * 
     * @param Domain $domain
     * 
     * @return Domain
     */
    public function createDomain(Domain $domain)
    {
        // Domain properties
        $propertyArray = $domain->toPropertyArray();
        $name = $propertyArray['zimbraDomainName'];

        // Do not send a zimbraDomainName or zimbraId attribute
        // The name is sent in the <name> tag and zimbraId shouldn't be sent when creating a new domain!
        unset($propertyArray['zimbraId']);
        unset($propertyArray['zimbraDomainName']);

        $properties = array(
            'name'       => $name,
            'attributes' => $propertyArray
        );

        $response = $this->soapClient->request('CreateDomainRequest', array(), $properties);
        $domain = $response->children()->CreateDomainResponse->children();
        
        return Domain::createFromXml($domain[0]);
    }

    /**
     * Updates a domain in the ZCS soap webservice
     * 
     * @param Domain $domain
     * 
     * @return Domain
     */
    public function updateDomain(Domain $domain)
    {
        // Domain properties
        $propertyArray = $domain->toPropertyArray();
        $id = $domain->getId();

        // Do not send a zimbraDomainName or zimbraId attribute
        // The name is immutable and zimbraId shouldn't be sent when updating a domain!
        unset($propertyArray['zimbraId']);
        unset($propertyArray['zimbraDomainName']);

        $properties = array(
            'id'         => $id,
            'attributes' => $propertyArray
        );

        $response = $this->soapClient->request('ModifyDomainRequest', array(), $properties);
        $domain = $response->children()->ModifyDomainResponse->children();
        
        return Domain::createFromXml($domain[0]);
    }

    /**
     * Removes a domain from the ZCS webservice
     * 
     * @warning This method is also used to delete Domain aliasses since in Zimbra a domain alias is just a Domain of a different type
     *          
     * @see DomainAdmin::deleteDomainAlias
     * 
     * @param                                  $domain
     * @param bool                             $deleteAliasses
     * @param bool                             $deleteAccounts
     * 
     * @throws \InvalidArgumentException
     * 
     * @return bool
     */
    public function deleteDomain($domain, $deleteAliasses = true, $deleteAccounts = false)
    {
        if($domain instanceof Entity){
            $domainId = $domain->getId();
        } elseif(is_string($domain)) {
            $domainId = $domain;
            $domain = $this->getDomain($domainId);
        } else {
            throw new \InvalidArgumentException(__METHOD__ . ' only accepts the ID of a domain or a Domain Entity');
        }

        // Remove all accounts from a domain
        if($deleteAccounts == true){
            $this->deleteAllDomainAccounts($domain);
        }

        // Remove all aliasses from a domain
        if($deleteAliasses == true) {
            $this->deleteAllDomainAliasses($domain);
        }

        $response = $this->soapClient->request('DeleteDomainRequest', array(), array(
            'id' => $domainId
        ));

        return true;
    }

}
