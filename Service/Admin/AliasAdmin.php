<?php
/**
 * Admin class to query the ZCS api for alias related requests.
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 */
namespace Jlaso\ZimbraSoapApiBundle\Service\Admin;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Admin;
use Jlaso\ZimbraSoapApiBundle\Entity\Account;
use Jlaso\ZimbraSoapApiBundle\Exception\WebserviceException;
use Jlaso\ZimbraSoapApiBundle\Service\ZCS\SoapClient;
use Jlaso\ZimbraSoapApiBundle\Entity\Alias;
use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Exception;

class AliasAdmin extends Admin
{
    /**
     * Fetches a single alias from the webservice and returns it
     * as a Alias object
     *
     * @param string $aliasId
     *
     * @throws Exception
     * 
     * @return Alias
     */
    public function getAlias($aliasId)
    {
        $attributes = array(
            'types' => 'aliases'
        );
        $params = array(
            'query' => sprintf('(zimbraId=%s)', $aliasId)
        );

        $response = $this->soapClient->request('SearchDirectoryRequest', $attributes, $params);

        $hits = intval((string)$response->children()->SearchDirectoryResponse['searchTotal']);
        if($hits <= 0) {
            // In case there are no aliasses found we simulate a soap fault to streamline the API
            throw SoapClient::getExceptionForFault('account.NO_SUCH_ALIAS');
        } else {
            $aliasList = $response->children()->SearchDirectoryResponse->children();
            return Alias::createFromXml($aliasList[0]);
        }
    }

    /**
     * Fetches all aliasses for an account from the soap webservice and returns them as an array
     * containing Alias objects
     * 
     * @param string $accountId The id of the account you are looking things up for
     *                           
     * @return array
     */
    public function getAliasListByAccount($accountId)
    {
        // Check if the account exists
//        $accountAdmin = new AccountAdmin($this->getSoapClient());
//        $account = $accountAdmin->getAccount($accountId);

        $attributes = array(
            'types' => 'aliases'
        );
        $params = array(
            'query' => sprintf('(zimbraAliasTargetId=%s)', $accountId)
        );

        $response = $this->soapClient->request('SearchDirectoryRequest', $attributes, $params);
        $aliasList = $response->children()->SearchDirectoryResponse->children();

        $results = array();
        foreach ($aliasList as $alias) {
            $results[] = Alias::createFromXml($alias);
        }

        return $results;
    }

    /**
     * Creates a new alias in the ZCS soap webservice
     *
     * NOTE: Due to the limitation of the webservice in ZCS we can't return the newly
     * created alias or even the ID of the new alias, there is no way to identify the
     * newly created alias unfortunately
     *
     * @param Alias $alias
     *
     * @throws WebserviceException
     *
     * @return boolean
     */
    public function createAlias(Alias $alias)
    {
        $properties = array(
            'id'    => $alias->getTargetid(),
            'alias' => $alias->getName()
        );

        $response = $this->soapClient->request('AddAccountAliasRequest', array(), $properties);

        return true;
    }

    /**
     * Removes an account alias from the ZCS webservice
     *
     * @param string $aliasId
     *
     * @return bool
     */
    public function deleteAlias($aliasId)
    {
        $alias = $this->getAlias($aliasId);
        $attributes = array();

        $response = $this->soapClient->request('RemoveAccountAliasRequest', $attributes, array(
            'id'    => $alias->getTargetid(),
            'alias' => $alias->getName()
        ));

        return true;
    }


}
