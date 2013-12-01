<?php

/**
 * A Class of Service.
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * @author Joseluis Laso <jlaso@joseluislaso.es>  Refactoring to adapt to PSR and bundle namespaces
 */

namespace Jlaso\ZimbraSoapApiBundle\Entity;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContext;
use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Entity;

class Cos extends Entity
{
    /**
     * The name of this COS
     * @property
     * @var String
     */
    private $name;

    /**
     * Extra field mapping
     * @var array
     */
    protected static $_datamap = array(
        'cn' => 'name'
    );

    /**
     * Validation for the properties of this Entity
     *
     * @static
     * @param \Symfony\Component\Validator\Mapping\ClassMetadata $metadata
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // Name should never be NULL or a blank string
        $metadata->addPropertyConstraint('name', new Assert\NotNull());
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
    }

    /**
     * @param String $name
     *
     * @return Cos
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }
}
