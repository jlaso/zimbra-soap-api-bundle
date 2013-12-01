<?php

/**
 * Exception that is raised when an entity is about to be created or
 * sent to the API that contains invalid data
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * @author Joseluis Laso <jlaso@joseluislaso.es>  Refactoring to adapt name and bundle namespaces
 */

namespace Jlaso\ZimbraSoapApiBundle\Exception;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Exception;
use Symfony\Component\Validator\ConstraintViolation;

class InvalidEntityException extends Exception
{
    /** @var ConstraintViolation[] */
    protected $violations;

    public function __construct($violations, $message="Something went wrong validating this Entity!", $code=0, $previous=null)
    {
        $this->violations = $violations;
        parent::__construct($message, $code, $previous);
    }

    public function getErrors()
    {
        $violations = array();
        foreach($this->violations as $violation){
            $property = $violation->getPropertyPath();
            $violations[] = array(
                'property' => $property,
                'errormessage' => sprintf("%s (received value: %s)", $violation->getMessage(), var_export($violation->getInvalidValue(), true))
            );
        }

        return $violations;
    }
}