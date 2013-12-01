<?php

/**
 * This Exception is thrown when the Zimbra webservice returns
 * a fault/error, the exception message is set to the Zimbra error returned
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * @author Joseluis Laso <jlaso@joseluislaso.es>  Refactoring to adapt name and bundle namespaces
 */
namespace Jlaso\ZimbraSoapApiBundle\Exception;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Exception;

class WebserviceException extends Exception
{

}
