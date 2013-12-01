<?php

/**
 * Exception that is raised when an entity is not found on the ZCS server
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * @author Joseluis Laso <jlaso@joseluislaso.es>  Refactoring to adapt name and bundle namespaces
 */
namespace Jlaso\ZimbraSoapApiBundle\Exception;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Exception;

class EntityNotFoundException extends Exception
{
    const ERR_DOMAIN_NOT_FOUND  = 1201;
    const ERR_ACCOUNT_NOT_FOUND = 1202;
    const ERR_ALIAS_NOT_FOUND   = 1203;
    const ERR_COS_NOT_FOUND     = 1204;

    public function __construct($message="This entity cannot be found!", $code=0, $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }
}
