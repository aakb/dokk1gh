<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ActionLogBundle\Service;

use ActionLogBundle\Entity\EntityActionLogEntry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;

class EntityActionLogger
{
    /** @var EntityManagerInterface */
    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function log($entity, $message, array $context = null)
    {
        list($entityType, $entityId) = $this->getEntityTypeAndId($entity);
        $entry = new EntityActionLogEntry($entityType, $entityId, $message, $context);
        $this->manager->persist($entry);
        $this->manager->flush();
    }

    public function getActionLogEntries($entity, array $criteria = [], array $orderBy = null)
    {
        list($entityType, $entityId) = $this->getEntityTypeAndId($entity);
        $criteria += [
            'entityType' => $entityType,
            'entityId' => $entityId,
        ];
        if (null === $orderBy) {
            $orderBy = ['createdAt' => Criteria::DESC];
        }

        return $this->manager->getRepository(EntityActionLogEntry::class)->findBy($criteria, $orderBy);
    }

    private function getEntityTypeAndId($entity)
    {
        if (!is_object($entity)) {
            throw new \RuntimeException('Entity must be an object');
        }
        $entityType = get_class($entity);

        if (method_exists($entity, 'getId')) {
            $entityId = $entity->getId();
        } elseif (method_exists($entity, 'id')) {
            $entityId = $entity->id();
        } elseif (isset($entity->id)) {
            $entityId = $entity->id;
        } else {
            throw new \RuntimeException('Cannot get entity id');
        }

        return [$entityType, $entityId];
    }
}
