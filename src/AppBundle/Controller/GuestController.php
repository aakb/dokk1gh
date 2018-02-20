<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Guest;
use AppBundle\Service\GuestService;
use AppBundle\Service\TemplateManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GuestController extends AdminController
{
    /** @var GuestService */
    private $guestService;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TemplateManager $templateManager,
        \Twig_Environment $twig,
        GuestService $guestService
    ) {
        parent::__construct($tokenStorage, $templateManager, $twig);
        $this->guestService = $guestService;
    }

    public function createNewGuestEntity()
    {
        return $this->guestService->createNewGuest();
    }

    public function showAppAction()
    {
        $id = $this->request->query->get('id');
        $guest = $this->em->getRepository(Guest::class)->find($id);

        return $this->redirectToRoute('app_code', [
            'guest' => $guest->getId(),
        ]);
    }

    public function sendAppAction()
    {
        $id = $this->request->query->get('id');
        $guest = $this->em->getRepository(Guest::class)->find($id);
        $appUrl = $this->generateUrl('app_code', ['guest' => $guest->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        if ($this->guestService->sendApp($guest, $appUrl)) {
            $this->addFlash('info', 'App sent');
        }

        return $this->redirectToReferrer();
    }
}
