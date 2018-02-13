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
use AppBundle\Entity\Guest;

class MailHelper
{
    const MAIL_SENT = 'MAIL_SENT';

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EntityActionLogger */
    private $actionLogger;

    /** @var Configuration */
    private $configuration;

    /** @var TwigHelper */
    private $twigHelper;

    public function __construct(
        \Swift_Mailer $mailer,
        EntityActionLogger $actionLogger,
        Configuration $configuration,
        TwigHelper $twigHelper
    ) {
        $this->mailer = $mailer;
        $this->actionLogger = $actionLogger;
        $this->twigHelper = $twigHelper;
        $this->configuration = $configuration;
    }

    public function sendApp(Guest $guest)
    {
        $fromEmail = $this->configuration->get('guest_app_email_sender_email');
        $fromName = $this->configuration->get('guest_app_email_sender_name');
        $from = [$fromEmail => $fromName];
        $recipient = $guest->getEmail();
        $template =
        $subject = $this->twigHelper->renderTemplate(
            $this->configuration->get('guest_app_email_subject_template'),
            [
                'guest' => $guest,
            ]
        );
        $body = $this->twigHelper->renderTemplate(
            $this->configuration->get('guest_app_email_body_template'),
            [
                'guest' => $guest,
            ]
        );

        $message = (new \Swift_Message($subject))
            ->setFrom($from)
            ->setTo($recipient)
            ->setBody($body, 'text/html');

        $this->mailer->send($message);
        $this->actionLogger->log($guest, self::MAIL_SENT, [
            'message' => $body,
            'guest' => $guest,
        ]);
    }
}
