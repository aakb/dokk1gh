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
     * @Route("/log/latest", name="sms_log_latest")
     */
    public function logLastestAction()
    {
        $items = [$this->manager->findOne(SmsGatewayActionLogEntry::class)];

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
        $username = $request->get('username');
        $password = $request->get('password');
        $countryCode = $request->get('countrycode');
        $number = $request->get('number');
        $message = $request->get('message');
        $callbackUrl = $request->get('callbackurl');

        $messageId = -1;
        $status = -1;
        $statusDescription = 'error';

        if (empty($username) || $username !== $password) {
            $status = 87;
            $statusDescription = 'Invalid credentials';
        } else {
            if ($countryCode && $number && $message) {
                $messageId = uniqid();
                $status = 0;
                $statusDescription = 'ok';
            } else {
                $status = 42;
                $statusDescription = 'Missing country, number or message';
            }
        }

        $this->manager->log(new SmsGatewayActionLogEntry('send_sms', [
            'countrycode' => $countryCode,
            'number' => $number,
            'message' => $message,
            'msg_id' => $messageId,
            'status' => $status,
            'status_description' => $statusDescription,
        ]));

        if (null !== $callbackUrl) {
            $url = $callbackUrl
                .(false === strpos($callbackUrl, '?') ? '?' : ':')
                .http_build_query([
                                      'msg_id' => $messageId,
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
