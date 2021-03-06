<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Entity;

use AppBundle\Traits\BlameableEntity;
use AppBundle\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity
 * @Gedmo\SoftDeleteable
 * @UniqueEntity(
 *   fields="email",
 *   message="This email is already in use."
 * )
 * @UniqueEntity(
 *   fields="aeosId",
 *   message="This aeosId is already in use."
 * )
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    use BlameableEntity;
    use SoftDeleteableEntity;
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="gdpr_accepted_at", type="datetime", nullable=true)
     */
    protected $gdprAcceptedAt;

    /**
     * @var string
     * @Email
     */
    protected $email;

    /**
     * @ORM\ManyToMany(targetEntity=Template::class)
     */
    protected $templates;

    /**
     * @var string
     *
     * @Assert\AeosPersonId
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $aeosId;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $apiKey;

    /**
     * Virtual property only used for displaying any AEOS person connected to this User.
     */
    private $aeosData;

    public function __construct()
    {
        parent::__construct();
        $this->templates = new ArrayCollection();
    }

    public function getGdprAcceptedAt()
    {
        return $this->gdprAcceptedAt;
    }

    public function setGdprAcceptedAt($gdprAcceptedAt)
    {
        $this->gdprAcceptedAt = $gdprAcceptedAt;

        return $this;
    }

    public function setTemplates(ArrayCollection $templates)
    {
        $this->templates = $templates;

        return $this;
    }

    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * Set aeosId.
     *
     * @param string $aeosId
     *
     * @return \AppBundle\Entity\Template|\AppBundle\Entity\User
     */
    public function setAeosId($aeosId)
    {
        $this->aeosId = $aeosId;

        return $this;
    }

    /**
     * Get aeosId.
     *
     * @return string
     */
    public function getAeosId()
    {
        return $this->aeosId;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function setAeosData($aeosPerson)
    {
        $this->aeosData = $aeosPerson;

        return $this;
    }

    public function getAeosData()
    {
        return $this->aeosData;
    }

    /**
     * @Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if (0 === $this->getTemplates()->count()) {
            $context->buildViolation('At least one template is required.')
                ->atPath('templates')
                ->addViolation();
        }
    }
}
