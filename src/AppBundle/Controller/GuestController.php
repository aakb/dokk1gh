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
        $guest = new Guest();
        $guest->setEnabled(true);

        return $guest;
    }

    public function showAppAction()
    {
        $id = $this->request->query->get('id');
        $guest = $this->em->getRepository(Guest::class)->find($id);

        return $this->redirectToRoute('app_main', [
            'guest' => $guest->getId(),
        ]);
    }

    public function resendAppAction()
    {
        $id = $this->request->query->get('id');
        $guest = $this->em->getRepository(Guest::class)->find($id);

        if ($this->guestService->resendApp($guest)) {
            $this->addFlash('info', 'App resent');
        }

        return $this->redirectToReferrer();
    }
}
