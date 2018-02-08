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

    /** @var array */
    private $configuration;

    public function __construct(
        AeosHelper $aeosHelper,
        EntityManagerInterface $manager,
        \Twig_Environment $twig,
        array $configuration
    ) {
        $this->aeosHelper = $aeosHelper;
        $this->manager = $manager;
        $this->twig = $twig;
        $this->configuration = $configuration;
    }

    public function isValid(Guest $guest)
    {
        $now = new \DateTime();

        return $guest->isEnabled()
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
                ->setEndTime(new \DateTime($this->configuration['guest_code']['duration']));

            $visitorName = $this->twig
                ->createTemplate($this->configuration['guest_code']['name_template'])
                ->render([
                             'guest' => $guest,
                             'template' => $template,
                         ]);

            $this->aeosHelper->createAeosIdentifier($code, $visitorName);
            $this->manager->persist($code);
            $this->manager->flush();

            return $code;
        } catch (\Exception $ex) {
            throw $ex;
            throw new GuestException('Cannot generate code');
        }
    }
}
