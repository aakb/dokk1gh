<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

use AppBundle\Entity\Code;
use AppBundle\Entity\Guest;
use AppBundle\Entity\Template;
use AppBundle\Exception\GuestException;
use AppBundle\Exception\InvalidTemplateException;
use Doctrine\ORM\EntityManagerInterface;

class GuestService
{
    /** @var AeosHelper */
    private $aeosHelper;

    /** @var EntityManagerInterface */
    private $manager;

    /** @var \Twig_Environment */
    private $twig;

    /** @var Configuration */
    private $configuration;

    /** @var SmsHelper */
    private $smsHelper;

    /** @var MailerInterface */
    private $mailHelper;

    public function __construct(
        AeosHelper $aeosHelper,
        EntityManagerInterface $manager,
        \Twig_Environment $twig,
        Configuration $configuration,
        SmsHelper $smsHelper,
        MailHelper $mailHelper
    ) {
        $this->aeosHelper = $aeosHelper;
        $this->manager = $manager;
        $this->twig = $twig;
        $this->configuration = $configuration;
        $this->smsHelper = $smsHelper;
        $this->mailHelper = $mailHelper;
    }

    /**
     * Send app url to user via sms and email.
     *
     * @param Guest  $guest
     * @param string $appUrl
     *
     * @return bool
     */
    public function sendApp(Guest $guest, $appUrl)
    {
        if (null !== $guest->getPhone()) {
            $this->smsHelper->sendApp($guest, $appUrl);
        }
        if (null !== $guest->getEmail()) {
            $this->mailHelper->sendApp($guest, $appUrl);
        }

        return true;
    }

    public function activate(Guest $guest)
    {
        $guest->setActivatedAt(new \DateTime());
        $this->manager->persist($guest);
        $this->manager->flush();
    }

    public function isValid(Guest $guest)
    {
        $now = new \DateTime();

        return $guest->isEnabled()
            && null !== $guest->getActivatedAt()
            && $guest->getActivatedAt() <= $now
            && $guest->getStartTime() <= $now
            && $now <= $guest->getEndTime();
    }

    public function canRequestCode(Guest $guest)
    {
        if (!$this->isValid($guest)) {
            return false;
        }

        return true;
    }

    public function generateCode(Guest $guest, Template $template)
    {
        if (!$guest->getTemplates()->contains($template)) {
            throw new InvalidTemplateException('Guest does not have access to template', [
                'guest' => $guest,
                'template' => $template,
            ]);
        }

        try {
            $code = new Code();
            $code
                ->setCreatedBy($guest->getCreatedBy())
                ->setTemplate($template)
                ->setStartTime(new \DateTime())
                ->setEndTime(new \DateTime($this->configuration->get('guest_code_duration')));

            $visitorName = $this->twig
                ->createTemplate($this->configuration->get('guest_code_name_template'))
                ->render([
                             'guest' => $guest,
                             'template' => $template,
                         ]);

            $this->aeosHelper->createAeosIdentifier($code, $visitorName);
            $this->manager->persist($code);
            $this->manager->flush();

            return $code;
        } catch (\Exception $ex) {
            throw new GuestException('Cannot generate code');
        }
    }
}
