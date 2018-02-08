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
    private $twig;

    public function __construct(
        SmsServiceInterface $smsService,
        EntityActionLogger $actionLogger,
        Configuration $configuration,
        \Twig_Environment $twig
    ) {
        $this->smsService = $smsService;
        $this->actionLogger = $actionLogger;
        $this->twig = $twig;
        $this->configuration = $configuration;
    }

    public function sendCode(Guest $guest, Code $code)
    {
        $recipient = $guest->getPhone();
        $template = $this->configuration->get('guest_code_sms_template');
        $message = $this->twig
            ->createTemplate($template)
            ->render([
                'guest' => $guest,
                'code' => $code,
            ]);
        $this->smsService->send($recipient, $message);
        $this->actionLogger->log($guest, self::SMS_SENT, [
            'code' => $code,
        ]);
    }
}
