<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Template;
use AppBundle\Entity\User;
use AppBundle\Service\AeosService;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class AeosEventSubscriber implements EventSubscriberInterface
{
    /** @var \AppBundle\Service\AeosService */
    private $aeosService;

    public function __construct(AeosService $aeosService)
    {
        $this->aeosService = $aeosService;
    }

    public static function getSubscribedEvents()
    {
        return [
            EasyAdminEvents::PRE_EDIT => ['loadAeosData'],
            EasyAdminEvents::PRE_SHOW => ['loadAeosData'],
        ];
    }

    public function loadAeosData(GenericEvent $event)
    {
        $entity = $event->getArgument('request')->attributes->get('easyadmin')['item'];

        if ($entity instanceof Template) {
            $template = $this->aeosService->getTemplate($entity->getAeosId());
            $entity->setAeosTemplate($template);
        } elseif ($entity instanceof User) {
            $person = $this->aeosService->getPerson($entity->getAeosId());
            $entity->setAeosPerson($person);
        }
    }
}
