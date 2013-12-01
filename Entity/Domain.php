<?php

/**
 * A Domain.
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

class Domain extends Entity
{
    /**
     * The Zimbra ID of the default COS for this Domain
     * @property
     * @var string
     */
    private $defaultCosId = null;

    /**
     * The name for this domain
     * @property
     * @var string
     */
    private $name = null;

    /**
     * The domain status (active, locked, closed, maintenance, suspended)
     * @property
     * @var string
     */
    private $domainStatus = null;

    /**
     * Validation for the properties of this Entity
     *
     * @static
     * @param \Symfony\Component\Validator\Mapping\ClassMetadata $metadata
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // Name should never be NULL or a blank string
        $metadata->addPropertyConstraint('name', new Assert\NotNull(array(
            'groups' => array('create')
        )));
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(array(
            'groups' => array('create')
        )));

        // Domain status has fixed set of options and is required
        $metadata->addPropertyConstraint('domainStatus', new Assert\Choice(array(
            'groups' => array('create', 'update'),
            'choices' => array('active', 'closed', 'locked', 'pending', 'maintenance')
        )));
        $metadata->addPropertyConstraint('domainStatus', new Assert\NotNull(array(
            'groups' => array('create')
        )));

        // DefaultCosId should either be a non-blank string or NULL
        $metadata->addConstraint(new Assert\Callback(array('_validateDefaultCosId')));
    }

    public function _validateDefaultCosId(ExecutionContext $context)
    {
        if(!is_null($this->getDefaultCosId()) && '' == trim($this->getDefaultCosId())){
            $context->addViolationAtPath('defaultCosId', 'defaultCosId must be null or a valid Cos ID', array(), $this->getDefaultCosId());
        }
    }

    /**
     * Extra field mapping
     * @var array
     */
    protected static $_datamap = array(
        'zimbraDomainDefaultCOSId' => 'defaultCosId',
        'zimbraDomainName' => 'name',
        'zimbraDomainStatus' => 'domainStatus'
    );

    /**
     * @param $defaultCosId
     *
     * @return Domain
     */
    public function setDefaultCosId($defaultCosId)
    {
        $this->defaultCosId = $defaultCosId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultCosId()
    {
        return $this->defaultCosId;
    }

    /**
     * @param $name
     *
     * @return Domain
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $status
     *
     * @return Domain
     */
    public function setDomainStatus($status)
    {
        $this->domainStatus = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomainStatus()
    {
        return $this->domainStatus;
    }

}
