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
use AppBundle\Entity\Template;
use AppBundle\Service\GuestService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AppController.
 *
 * @Route("/app/{guest}")
 */
class AppController extends Controller
{
    /** @var GuestService */
    private $guestService;

    public function __construct(GuestService $guestService)
    {
        $this->guestService = $guestService;
    }

    /**
     * @Route("", name="app_code")
     * @Method("GET")
     */
    public function codeAction(Guest $guest)
    {
        $isValid = $this->guestService->isValid($guest);
        $canRequestCode = $this->guestService->canRequestCode($guest);

        return $this->render('app/code/index.html.twig', [
            'guest' => $guest,
            'guest_is_valid' => $isValid,
            'guest_can_request_code' => $canRequestCode,
        ]);
    }

    /**
     * @Route("/request/{template}", name="app_code_request")
     * @Method("POST")
     */
    public function codeRequestAction(Guest $guest, Template $template)
    {
        if (!$guest->getTemplates()->contains($template)) {
            throw new HttpException(400, 'Invalid template');
        }

        $code = null;
        $codeExpiresAt = new \DateTime('+10 minutes');
        $succes = 1 === $template->getId();
        $errorMessage = null;

        if ($succes) {
            $this->addFlash('gh_message_success_'.$template->getId(), __METHOD__);
            $this->addFlash('gh_message_info_'.$template->getId(), __METHOD__);
        } else {
            $this->addFlash('gh_message_danger_'.$template->getId(), __METHOD__);
            $this->addFlash('gh_message_warning_'.$template->getId(), __METHOD__);
        }

        return $this->redirectToRoute('app_code', [
            'guest' => $guest->getId(),
        ]);
    }

    /**
     * @Route("/card", name="app_card")
     */
    public function cardAction(Guest $guest)
    {
        return $this->render('app/card/index.html.twig', [
            'guest' => $guest,
        ]);
    }

    /**
     * @Route("/about", name="app_about")
     */
    public function aboutAction()
    {
        return $this->render('app/about/index.html.twig');
    }
}
