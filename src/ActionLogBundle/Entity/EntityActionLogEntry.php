<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ActionLogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("itkdev_entity_action_log_entry",
 *   indexes={
 *     @ORM\Index(name="entity_idx", columns={"entity_type", "entity_id"})
 *   }
 * )
 */
class EntityActionLogEntry
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_type", type="string", length=255)
     */
    private $entityType;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_id", type="string", length=255)
     */
    private $entityId;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255)
     */
    private $message;

    /**
     * @var array
     *
     * @ORM\Column(name="context", type="json_array", nullable=true)
     */
    private $context;

    public function __construct($entityType, $entityId, $message, array $context = null)
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
