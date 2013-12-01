<?php
/**
 * Zimbra SOAP API calls for the regular user account.
 * Mostly a copy of the Admin class.
 *
 * @author Reinier Pelayo
 */
namespace Jlaso\ZimbraSoapApiBundle\Service\ZCS;

abstract class Mail
{

    /**
     * The soapclient
     * @var SoapClient
     */
    protected $soapClient;

    /**
     * Constructor
     *
     * @param SoapClient $client
     */
    public function __construct(SoapClient $client)
    {
        $client->namespace = SoapClient::MAIL_NS;
        $this->setSoapClient($client);
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
