<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

use ActionLogBundle\Service\EntityActionLogger;
use AppBundle\Entity\Code;
use AppBundle\Entity\Guest;

class SmsHelper
{
    const SMS_SENT = 'SMS_SENT';

    /** @var \AppBundle\Service\SmsServiceInterface */
    protected $smsService;

    /** @var EntityActionLogger */
    private $actionLogger;

    /** @var Configuration */
    private $configuration;

    /** @var \Twig_Environment */
    private $twigHelper;

    public function __construct(
        SmsServiceInterface $smsService,
        EntityActionLogger $actionLogger,
        Configuration $configuration,
        TwigHelper $twigHelper
    ) {
        $this->smsService = $smsService;
        $this->actionLogger = $actionLogger;
        $this->twigHelper = $twigHelper;
        $this->configuration = $configuration;
    }

    public function sendApp(Guest $guest)
    {
        $recipient = $guest->getPhone();
        $template =
        $message = $this->twigHelper->renderTemplate(
            $this->configuration->get('guest_app_sms_template'),
            [
                'guest' => $guest,
            ]
        );
        $this->smsService->send($recipient, $message);

        $this->actionLogger->log($guest, self::SMS_SENT, [
            'guest' => $guest,
            'message' => $message,
        ]);
    }

    public function sendCode(Guest $guest, Code $code)
    {
        $recipient = $guest->getPhone();

        $message = $this->twigHelper->renderTemplate(
            $this->configuration->get('guest_code_sms_template'),
            [
                'guest' => $guest,
                'code' => $code,
            ]
        );
        $this->smsService->send($recipient, $message);

        $this->actionLogger->log($guest, self::SMS_SENT, [
            'guest' => $guest,
            'code' => $code,
            'message' => $message,
        ]);
    }
}
