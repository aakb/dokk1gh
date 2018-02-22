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

    /** @var TwigHelper */
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
        TwigHelper $twig,
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
     * Create a new guest with default values.
     *
     * @return Guest
     */
    public function createNewGuest()
    {
        $guest = new Guest();
        $guest
            ->setEnabled(true)
            ->setStartTime(new \DateTime($this->configuration->get('guest_default_startTime')))
            ->setEndTime(new \DateTime($this->configuration->get('guest_default_endTime')));

        return $guest;
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

        $timezone = new \DateTimeZone($this->configuration->get('view_timezone'));
        $now = new \DateTime('now', $timezone);

        $timeRanges = $guest->getTimeRanges();
        $day = $now->format('N');
        if (!isset($timeRanges['start_time_'.$day], $timeRanges['end_time_'.$day])
            || !preg_match('/^(?<hours>\d{2}):(?<minutes>\d{2})$/', $timeRanges['start_time_'.$day], $startTimeData)
            || !preg_match('/^(?<hours>\d{2}):(?<minutes>\d{2})$/', $timeRanges['end_time_'.$day], $endTimeData)) {
            return false;
        }

        $startTime = clone $now;
        $startTime->setTime($startTimeData['hours'], $startTimeData['minutes']);
        $endTime = clone $now;
        $endTime->setTime($endTimeData['hours'], $endTimeData['minutes']);

        if ($now < $startTime || $endTime < $now) {
            return false;
        }

        return true;
    }

    public function generateCode(Guest $guest, Template $template, $note = null)
    {
        if (!$this->canRequestCode($guest)) {
            throw new GuestException('Guest cannot request code right now', [
                'guest' => $guest,
            ]);
        }

        if (!$guest->getTemplates()->contains($template)) {
            throw new InvalidTemplateException('Guest does not have access to template', [
                'guest' => $guest,
                'template' => $template,
            ]);
        }

        try {
            $code = new Code();
            $code
                ->setNote($note)
                ->setCreatedBy($guest->getCreatedBy())
                ->setTemplate($template)
                ->setStartTime(new \DateTime())
                ->setEndTime(new \DateTime($this->configuration->get('guest_code_duration')));

            $visitorName = $this->twig
                ->renderTemplate($this->configuration->get('guest_code_name_template'), [
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

    public function sendCode(Guest $guest, Code $code)
    {
        $this->smsHelper->sendCode($guest, $code);
    }
}
