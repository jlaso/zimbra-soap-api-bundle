<?php

/**
 * The exception is thrown when an error communicating with the soap webservice occurs
 * When an error response from the webservice is returned on the other hand
 * a WebserviceException exception will be thrown!
 *
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * @author Joseluis Laso <jlaso@joseluislaso.es>  Refactoring to adapt name and bundle namespaces
 */
namespace Jlaso\ZimbraSoapApiBundle\Exception;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Exception;

class SoapException extends Exception
{

}
