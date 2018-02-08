<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Entity;

use AppBundle\Traits\BlameableEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Guest.
 *
 * @ORM\Table
 * @ORM\Entity
 */
class Guest
{
    use BlameableEntity;
    use SoftDeleteableEntity;
    use TimestampableEntity;

    /**
     * @ORM\ManyToMany(targetEntity=Template::class)
     */
    protected $templates;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $enabled;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $company;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $phone;

    /**
     * @var string
     *
     * @Assert\Email()
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $endTime;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $timeRanges;

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Guest
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set company.
     *
     * @param string $company
     *
     * @return Guest
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * Get company.
     *
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return Guest
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Guest
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param mixed $templates
     *
     * @return Guest
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $startTime
     *
     * @return Guest
     */
    public function setStartTime(\DateTime $startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param \DateTime $endTime
     *
     * @return Guest
     */
    public function setEndTime(\DateTime $endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * @return array
     */
    public function getTimeRanges()
    {
        return $this->timeRanges;
    }

    /**
     * @param array $timeRanges
     */
    public function setTimeRanges(array $timeRanges)
    {
        $this->timeRanges = $timeRanges;

        return $this;
    }

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context)
    {
        if (0 === $this->getTemplates()->count()) {
            $context->buildViolation('At least one template is required.')
                ->atPath('templates')
                ->addViolation();
        }
        if ($this->getEndTime() <= new \DateTime()) {
            $context->buildViolation('End time must be after now.')
                ->atPath('endTime')
                ->addViolation();
        } elseif ($this->getStartTime() >= $this->getEndTime()) {
            $context->buildViolation('End time must be greater than start time.')
                ->atPath('endTime')
                ->addViolation();
        }
    }
}
