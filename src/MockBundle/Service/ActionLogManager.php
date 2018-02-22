<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace MockBundle\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use MockBundle\Entity\ActionLogEntry;

class ActionLogManager
{
    /** @var EntityManagerInterface */
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function log(ActionLogEntry $entry)
    {
        $this->manager->persist($entry);
        $this->manager->flush();
    }

    public function findAll($className, array $criteria = [], array $orderBy = ['createdAt' => Criteria::DESC])
    {
        return $this->manager->getRepository($className)->findBy($criteria, $orderBy);
    }

    public function findOne($className, array $criteria = [], array $orderBy = ['createdAt' => Criteria::DESC])
    {
        return $this->manager->getRepository($className)->findOneBy($criteria, $orderBy);
    }
}
