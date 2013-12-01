<?php

/**
 * Handles the assembling of the low-level XML SOAP message
 *
 * @author LiberSoft <info@libersoft.it>
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Jlaso\ZimbraSoapApiBundle\Service\ZCS;

use Jlaso\ZimbraSoapApiBundle\Exception\EntityNotFoundException;
use Jlaso\ZimbraSoapApiBundle\Exception\SoapException;
use Jlaso\ZimbraSoapApiBundle\Exception\WebserviceException;
use Symfony\Component\DependencyInjection\SimpleXMLElement;

class SoapClient
{
    /**
     * Namespace constants
     */
    const ACCOUNT_NS   = 'urn:zimbraAccount';
    const ADMIN_NS     = 'urn:zimbraAdmin';
    const ADMIN_EXT_NS = 'urn:zimbraAdminExt';
    const MAIL_NS      = 'urn:zimbraMail';
    const REPL_NS      = 'urn:zimbraRepl';
    const SYNC_NS      = 'urn:zimbraSync';
    const VOICE_NS     = 'urn:zimbraVoice';

    /**
     * The XML message that is going to be sent to the Soap Server
     * @var SimpleXMLElement
     */
    private $message;

    /**
     * Pointer to the context element from the $message
     * @var SimpleXMLElement
     */
    private $context;

    /**
     * The curl client
     * @var CurlClient
     */
    private $curlClient;

    /**
     * When true all XML will be outputted
     * @var bool
     */
    static public $debug = true;

    /**
     * Namespace command used
     * @var string
     */
    public $namespace = self::ACCOUNT_NS;

    /**
     * Constructor which initializes the connection to the receiving server
     *
     * @param array $parameters
     */
    public function __construct($parameters = array())
    {
        $parameters = array_merge(
            array(
                'server'    => null,
                'port'      => '7071',
                'username'  => 'admin',
                'password'  => null,
                'debug'     => true,
                'namespace' => self::ACCOUNT_NS,
            ),
            $parameters
        );
        //print_r($parameters);
        $server          = trim($parameters['server']);
        $port            = intval($parameters['port']);
        $username        = trim($parameters['username']);
        $password        = trim($parameters['password']);
        self::$debug     = (bool)$parameters['debug'];
        $this->namespace = $parameters['namespace'];

        // @codeCoverageIgnoreStart
        if($server && $port) {
            $curlClient = new CurlClient(sprintf("https://%s:%d/service/admin/soap", $server, $port));
            $this->setCurlClient($curlClient);
        }
        // @codeCoverageIgnoreEnd

        $this->message = new \SimpleXMLElement('<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"></soap:Envelope>');
        $this->context = $this->message->addChild('Header')->addChild('context', null, 'urn:zimbra');
        $this->message->addChild('Body');

        // @codeCoverageIgnoreStart
        if($username && $password && $this->getCurlClient()){
            $this->auth($username, $password);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Setter for the curl client
     *
     * @param CurlClient $curlClient
     *
     * @return SoapClient
     */
    public function setCurlClient(CurlClient $curlClient)
    {
        $curlClient
                ->setOption(CURLOPT_POST, TRUE)
                ->setOption(CURLOPT_RETURNTRANSFER, TRUE)
                ->setOption(CURLOPT_SSL_VERIFYPEER, FALSE)
                ->setOption(CURLOPT_SSL_VERIFYHOST, FALSE)
                ->setOption(CURLOPT_CONNECTTIMEOUT, 30);

        $this->curlClient = $curlClient;
        return $this;
    }

    /**
     * Getter for the curl client
     *
     * @return CurlClient
     */
    public function getCurlClient()
    {
        return $this->curlClient;
    }

    /**
     * Authenticate
     * @param string $username
     * @param string $password
     * @return string The authtoken received when logging in
     */
    public function auth($username, $password)
    {
        $xml = $this->request('AuthRequest', array(), array('account' => $username,'password' => $password));
        $authToken = $xml->children()->AuthResponse->authToken;
        $this->addContextChild('authToken', $authToken);
        return (string) $authToken;
    }

    /**
     * Returns the complete message as an XML string
     * @return string
     */
    public function getXml()
    {
        return $this->message->asXml();
    }

    /**
     * Sets a value on the context node of the XML request
     * @param $name Tagname for the context node
     * @param $value Tag value for the node
     */
    public function addContextChild($name, $value)
    {
        if (isset($this->context->$name)) {
            $this->context->$name = $value;
        } else {
            $this->context->addChild($name, $value);
        }
    }

    /**
     * Getter for the context element
     * @return \SimpleXMLElement
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * constructs a child with attributes passed in $data
     * underscore key is the main data of node
     *
     * @param string            $node
     * @param array             $data
     * @param \SimpleXMLElement $document
     *
     * @throws \Exception
     *
     * @return \SimpleXMLElement
     */
    public function constructNodeWithAttrs($node, $data, \SimpleXMLElement $document)
    {
        $result = null;
        foreach($data as $key=>$value)
        {
            if($key=='_'){
                $result = $document->addChild($node, $value);
                break;
            }
        }

        if($result){
            foreach($data as $key=>$value)
            {
                if($key!='_'){
                    $result->addAttribute($key, $value);
                }
            }
        }else{
            throw new \Exception('node error ' . print_r($data, true));
        }

        return $result;
    }

    /**
     * Sends an XML request to the SOAP server
     *
     * @param string $action The action you are performing, a soap method defined in the wsdl
     * @param array $attributes The attributes for the XML node that defines the action
     * @param array $params Request params
     *
     * @throws SoapException
     *
     * @return \SimpleXMLElement The response's Body tag
     *
     */
    public function request($action, $attributes = array(), $params = array())
    {
        // Sanity check to see if we have connected
        if (!$this->getCurlClient()){
            throw new SoapException('No valid connection has been established, have you connected and authenticated with the ZCS Soap webservice?');
        }

        unset($this->message->children('soap', true)->Body);
        $body = $this->message->addChild('Body');
        $actionChild = $body->addChild($action, null, $this->namespace);

        foreach ($attributes as $key => $value) {
            if(is_array($value)){
                $this->constructNodeWithAttrs($key, $value, $actionChild);

            }else{
                $actionChild->addAttribute($key, $value);
            }
        }

        $actionChild = self::formatRequestXml($params, $actionChild);

        if(self::$debug === true){
            echo "<h2>REQUEST</h2></hr />";
            echo '<pre>', htmlentities(self::formatXml($this->getXml())), '</pre>';
            die;
        }

        $this->getCurlClient()->setOption(CURLOPT_POSTFIELDS, $this->getXml());

        return $this->handleResponse($this->getCurlClient()->execute());
    }

    /**
     * Handles the response
     *
     * @param string $soapMessage The response
     *
     * @throws Exception
     *
     * @return \SimpleXMLElement The response XML <Body> tag
     */
    private function handleResponse($soapMessage)
    {
        // No message is returned, something went wrong, throw a Soap exception which
        // means there was an error communicating with the soap webservice`
        if (!$soapMessage) {
            throw new SoapException($this->getCurlClient()->getError(), $this->getCurlClient()->getErrorNr());
        }

        // Construct a SimpleXMLElement from the message
        $xml = new \SimpleXMLElement($soapMessage);

        if(self::$debug === true){
            echo "<h2>RESPONSE</h2></hr />";
            echo '<pre>', htmlentities(self::formatXml($xml->asXml())), '</pre>';
        }

        // If the response is a Fault throw a webservice exception
        $fault = $xml->children('soap', true)->Body->Fault;
        if ($fault) {
            throw self::getExceptionForFault($fault->Detail->children()->Error->Code->__toString());
        }

        // Return the body element from the XML
        return $xml->children('soap', true)->Body;
    }

    /**
     * Outputs a human readable version of the XML passed
     * @static
     * @param string $xml
     * @param bool $escape True to HTML escape the output
     * @return string
     */
    public static function formatXml($xml, $escape = false)
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        @$dom->loadXML($xml);
        $output = $dom->saveXML();
        return $escape ? htmlentities($output, ENT_QUOTES, 'utf-8') : $output;
    }

    /**
     * Takes an array and converts it to an xml child element of the given
     * child node.
     *
     * @param array $params
     * @param SimpleXml $child
     */
    public function formatRequestXml ($params, $child) {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                switch ($key) {
                    case 'attributes':
                        foreach ($value as $l => $b) {
                            if(is_bool($b)){
                                $b = ($b === true) ? 'TRUE' : 'FALSE';
                            }
                            $child->addAttribute($l, $b);
                        }
                        break;
                    default:
                        $childNode = $child->addChild($key);
                        self::formatRequestXml($value, $childNode);
                }
            } elseif (!is_numeric($key)) {
                $child->addChild($key, $value);
            }else{
                $child[0] = $value;
            }
        }
    }

    /**
     * @param $faultMessage
     *
     * @return Exception
     */
    public static function getExceptionForFault($faultMessage)
    {
        switch($faultMessage) {
            case 'account.NO_SUCH_DOMAIN':
                $exception = new EntityNotFoundException('Domain cannot be found', EntityNotFoundException::ERR_DOMAIN_NOT_FOUND);
                break;
            case 'account.NO_SUCH_ACCOUNT':
                $exception = new EntityNotFoundException('Account cannot be found', EntityNotFoundException::ERR_ACCOUNT_NOT_FOUND);
                break;
            case 'account.NO_SUCH_ALIAS':
                $exception = new EntityNotFoundException('Alias cannot be found', EntityNotFoundException::ERR_ALIAS_NOT_FOUND);
                break;
            case 'account.NO_SUCH_COS':
                $exception = new EntityNotFoundException('Cos cannot be found', EntityNotFoundException::ERR_COS_NOT_FOUND);
                break;
            default:
                $exception = new WebserviceException($faultMessage);
        }

        return $exception;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

}

