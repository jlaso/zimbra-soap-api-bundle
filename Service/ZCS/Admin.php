<?php
/**
 * Zimbra SOAP API calls.
 *
 * @author LiberSoft <info@libersoft.it>
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * @author Joseluis Laso <jlaso@joseluislaso.es>  Refactoring to adapt to PSR and bundle namespaces
 */
namespace Jlaso\ZimbraSoapApiBundle\Service\ZCS;

abstract class Admin
{

    /**
     * The soapclient
     * @var SoapClient
     */
    protected $soapClient;

    public function __construct($params = array())
    {
        $params['namespace'] = SoapClient::ADMIN_NS;
        $this->soapClient = new SoapClient($params);
    }

    /**
     * The setter for the Soap Client class
     *
     * @param SoapClient $soapClient
     *
     * @return Admin
     */
    public function setSoapClient($soapClient)
    {
        $this->soapClient = $soapClient;

        return $this;
    }

    /**
     * @return SoapClient
     */
    public function getSoapClient()
    {
        return $this->soapClient;
    }

}
