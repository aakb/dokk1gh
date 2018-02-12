<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace MockBundle\Controller;

use MockBundle\Entity\SmsGatewayActionLogEntry;
use MockBundle\Service\ActionLogManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/sms")
 */
class SmsGatewayController extends Controller
{
    /** @var ActionLogManager */
    private $manager;

    public function __construct(ActionLogManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route()
     */
    public function indexAction()
    {
        return $this->redirectToRoute('sms_log');
    }

    /**
     * @Route("/log", name="sms_log")
     */
    public function logAction()
    {
        $items = $this->manager->findAll(SmsGatewayActionLogEntry::class);

        return $this->render('@Mock/smsgateway/index.html.twig', [
            'items' => $items,
        ]);
    }

    /**
     * @Route("/send")
     * @Method({"GET", "POST"})
     */
    public function sendAction(Request $request)
    {
        $countryCode = $request->get('countrycode');
        $number = $request->get('number');
        $message = $request->get('message');
        $callbackUrl = $request->get('callbackurl');

        $messageId = 0;
        $status = -1;
        $statusDescription = 'error';

        if ($countryCode && $number && $message) {
            $this->manager->log(new SmsGatewayActionLogEntry('send_sms', [
                'countrycode' => $countryCode,
                'number' => $number,
                'message' => $message,
            ]));

            $messageId = random_int(1, PHP_INT_MAX);
            $status = 0;
            $statusDescription = 'ok';
        }

        if (null !== $callbackUrl) {
            $url = $callbackUrl
                .(false === strpos($callbackUrl, '?') ? '?' : ':')
                .http_build_query([
                                      'msg_id' => uniqid(),
                                      'status' => $status,
                                      'status_description' => $statusDescription,
                                  ]);
            $ch = curl_init($url);
            curl_exec($ch);
            curl_close($ch);
        }

        return new Response($status);
    }

    /**
     * @Route("/send/callback")
     * @Method("GET")
     */
    public function sendCallbackAction(Request $request)
    {
        $messageId = $request->get('msg_id');
        $status = $request->get('status');
        $statusDescription = $request->get('status_description');

        $this->manager->log(new SmsGatewayActionLogEntry('sent_sms', [
            'msg_id' => $messageId,
            'status' => $status,
            'status_description' => $statusDescription,
        ]));

        return new Response();
    }
}
