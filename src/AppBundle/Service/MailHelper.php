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

    public function sendApp(Guest $guest, $appUrl)
    {
        $fromEmail = $this->configuration->get('guest_app_email_sender_email');
        $fromName = $this->configuration->get('guest_app_email_sender_name');
        $from = [$fromEmail => $fromName];
        $recipient = $guest->getEmail();
        $subject = $this->twigHelper->renderTemplate(
            $this->configuration->get('guest_app_email_subject_template'),
            [
                'guest' => $guest,
            ]
        );
        $bodyHtml = $this->twigHelper->renderTemplate(
            $this->configuration->get('guest_app_email_body_template'),
            [
                'guest' => $guest,
                'app_url' => $appUrl,
            ]
        );

        $template = $this->twigHelper->load('app/email/app.html.twig');
        $context = [
            'app_url' => $appUrl,
            'guest' => $guest,
            'subject' => $subject,
            'body_html' => $bodyHtml,
        ];
        $subject = $template->renderBlock('subject', $context);
        $bodyHtml = $template->renderBlock('body_html', $context);

        $message = (new \Swift_Message($subject))
            ->setFrom($from)
            ->setTo($recipient)
            ->setBody($bodyHtml, 'text/html');

        $this->mailer->send($message);
        $this->actionLogger->log($guest, self::MAIL_SENT, [
            'message' => $bodyHtml,
            'guest' => $guest,
        ]);
    }
}
