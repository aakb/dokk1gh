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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /** @var Configuration */
    private $configuration;

    public function __construct(GuestService $guestService, Configuration $configuration)
    {
        $this->guestService = $guestService;
        $this->configuration = $configuration;
    }

    /**
     * @Route("/code", name="app_code")
     * @Method("GET")
     */
    public function codeAction(Guest $guest)
    {
        if (null === $guest->getActivatedAt()) {
            return $this->guideAction($guest);
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

        return $this->redirectToRoute('app_code', [
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
            $code = $this->guestService->generateCode($guest, $template, 'Created by app');
            if (null !== $code) {
                $status['code_generated'] = true;
                $this->guestService->sendCode($guest, $code);
                $status['sms_sent'] = true;
            }
        } catch (AbstractException $exception) {
            return $this->render('app/code/error.html.twig', [
                'message' => $exception->getMessage(),
                'guest' => $guest,
            ]);
            $messages['danger'][] = $exception->getMessage();
        }
        $this->setGeneratedCodeData([$status, $messages]);

        return $this->redirectToRoute('app_code_request_result', [
            'code' => $code ? $code->getId() : null,
            'guest' => $guest->getId(),
            'template' => $template->getId(),
        ]);
    }

    /**
     * @Route("/request/{template}", name="app_code_request_result")
     * @Method("GET")
     */
    public function codeRequestResultAction(Request $request, Guest $guest, Template $template)
    {
        list($status, $messages) = $this->getGeneratedCodeData();
        $codeId = $request->get('code');
        $code = null !== $codeId
            ? $this->container->get('doctrine')->getRepository(Code::class)->find($codeId)
            : null;

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
    public function aboutAction(Guest $guest)
    {
        return $this->render('app/about/index.html.twig', [
            'guest' => $guest,
            'replacements' => [
                'app://guide_url' => $this->generateUrl('app_guide', ['guest' => $guest->getId()]),
            ],
        ]);
    }

    /**
     * @Route("/mainfest.json", name="app_manifest")
     */
    public function manifestAction(Guest $guest)
    {
        $assets = $this->container->get('assets.packages');

        $manifest = [
            'short_name' => $this->configuration->get('pwa_app_short_name'),
            'name' => $this->configuration->get('pwa_app_name'),
            'icons' => array_map(function ($width) use ($assets) {
                $sizes = $width.'x'.$width;

                return [
                    'src' => $assets->getUrl($this->configuration->get('app_icons.'.$sizes)),
                    'sizes' => $sizes,
                    'type' => 'image/png',
                ];
            }, [20, 29, 40, 48, 58, 60, 72, 76, 80, 87, 96, 120, 144, 152, 167, 180, 192, 512, 1024]),
            'start_url' => $this->generateUrl('app_code', [
                'guest' => $guest->getId(),
                'utm_source' => 'homescreen',
            ]),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#003764',
            'theme_color' => '#003764',
            'lang' => 'da',
        ];

        return new JsonResponse($manifest);
    }

    /**
     * @Route("/offline", name="app_offline")
     */
    public function offlineAction()
    {
        return $this->render('app/offline.html.twig');
    }

    /**
     * @Route("/serviceworker.js", name="app_serviceworker")
     */
    public function serviceworkerAction()
    {
        $content = $this->renderView('app/javascripts/sw.js.twig');

        return new Response($content, 200, ['content-type' => 'text/javascript']);
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
