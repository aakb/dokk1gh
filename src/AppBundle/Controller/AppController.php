<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Code;
use AppBundle\Entity\Guest;
use AppBundle\Entity\Template;
use AppBundle\Exception\AbstractException;
use AppBundle\Service\Configuration;
use AppBundle\Service\GuestService;
use AppBundle\Service\SmsHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AppController.
 *
 * @Route("/app/{guest}")
 */
class AppController extends Controller
{
    const GENERATED_CODE_SESSION_KEY = 'generated_code';

    /** @var GuestService */
    private $guestService;

    /** @var SmsHelper */
    private $smsHelper;

    /** @var Configuration */
    private $configuration;

    public function __construct(GuestService $guestService, SmsHelper $smsHelper, Configuration $configuration)
    {
        $this->guestService = $guestService;
        $this->smsHelper = $smsHelper;
        $this->configuration = $configuration;
    }

    /**
     * @Route("", name="app_main")
     * @Method("GET")
     */
    public function codeAction(Guest $guest)
    {
        if (null === $guest->getActivatedAt()) {
            return $this->redirectToRoute('app_guide', [
                'guest' => $guest->getId(),
            ]);
        }

        $isValid = $this->guestService->isValid($guest);
        $canRequestCode = $this->guestService->canRequestCode($guest);

        return $this->render('app/code/index.html.twig', [
            'guest' => $guest,
            'guest_is_valid' => $isValid,
            'guest_can_request_code' => $canRequestCode,
        ]);
    }

    /**
     * @Route("/guide", name="app_guide")
     * @Method("GET")
     */
    public function guideAction(Guest $guest)
    {
        return $this->render('app/onboard-guide/index.html.twig', [
            'guest' => $guest,
        ]);
    }

    /**
     * @Route("/guide", name="app_accept")
     * @Method("POST")
     */
    public function acceptAction(Guest $guest)
    {
        $this->guestService->activate($guest);
        $this->addFlash('success', 'Guest accepted');

        return $this->redirectToRoute('app_main', [
            'guest' => $guest->getId(),
        ]);
    }

    /**
     * @Route("/request/{template}", name="app_code_request")
     * @Method("POST")
     */
    public function codeRequestAction(Guest $guest, Template $template)
    {
        $code = null;
        $messages = null;
        $status = [];

        try {
            $code = $this->guestService->generateCode($guest, $template);
            if (null !== $code) {
                $status['code_generated'] = true;
                $this->smsHelper->sendCode($guest, $code);
                $status['sms_sent'] = true;
            }
        } catch (AbstractException $exception) {
            $messages['danger'][] = $exception->getMessage();
        }
        $this->setGeneratedCodeData([$code, $status, $messages]);

        return $this->redirectToRoute('app_code_request_result', [
            'guest' => $guest->getId(),
            'template' => $template->getId(),
        ]);
    }

    /**
     * @Route("/request/{template}", name="app_code_request_result")
     * @Method("GET")
     */
    public function codeRequestResultAction(Guest $guest, Template $template)
    {
        list($code, $status, $messages) = $this->getGeneratedCodeData();

        return $this->render('app/code/request_result.html.twig', [
            'guest' => $guest,
            'template' => $template,
            'code' => $code,
            'messages' => $messages,
            'status' => $status,
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

    /**
     * @Route("/mainfest.json", name="app_manifest")
     */
    public function manifestAction(Guest $guest)
    {
        $assets = $this->container->get('assets.packages');

        $manifest = [
            'short_name' => $this->configuration->get('pwa_app_name'),
            'name' => $this->configuration->get('pwa_app_short_name'),
                'icons' => [
                    [
                        'src' => $assets->getUrl('images/code2dokk1_launcher_ic_2x.png'),
                        'sizes' => '96x96',
                        'type' => 'image/png',
                    ],
                    [
                        'src' => $assets->getUrl('images/code2dokk1_launcher_ic_3x.png'),
                        'sizes' => '144x144',
                        'type' => 'image/png',
                    ],
                    [
                        'src' => $assets->getUrl('images/code2dokk1_launcher_ic_4x.png'),
                        'sizes' => '192x192',
                        'type' => 'image/png',
                    ],
                    [
                        'src' => $assets->getUrl('images/code2dokk1_launcher_ic.png'),
                        'sizes' => '512x512',
                        'type' => 'image/png',
                    ],
                ],
                'start_url' => $this->generateUrl('app_main', [
                    'guest' => $guest->getId(),
                    'utm_source' => 'homescreen',
                ]),
                'display' => 'standalone',
                'orientation' => 'portrait',
                'background_color' => '#003764',
                'theme_color' => '#003764',
        ];

        return new JsonResponse($manifest);
    }

    private function setGeneratedCodeData($data)
    {
        $session = $this->container->get('session');
        $session->set(self::GENERATED_CODE_SESSION_KEY, $data);
    }

    private function getGeneratedCodeData($peek = false)
    {
        $session = $this->container->get('session');
        $data = null;
        if ($session->has(self::GENERATED_CODE_SESSION_KEY)) {
            $data = $session->get(self::GENERATED_CODE_SESSION_KEY);
            if (!$peek) {
                //            $session->remove(self::GENERATED_CODE_SESSION_KEY);
            }
        }

        return $data;
    }
}
