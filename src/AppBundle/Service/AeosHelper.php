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
use AppBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AeosHelper
{
    /** @var \AppBundle\Service\AeosService */
    protected $aeosService;

    /** @var \Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface */
    protected $tokenStorage;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var array */
    protected $configuration;

    public function __construct(
        AeosService $aeosService,
        TokenStorageInterface $tokenStorage,
        \Twig_Environment $twig,
        array $configuration
    ) {
        $this->aeosService = $aeosService;
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;

        if (!isset($configuration['vistor_name_template'])) {
            $configuration['vistor_name_template']
                = "Code #{{ code.id }}; {{ 'now'|date('Y-m-d H:i') }}; {{ code.createdBy.email }}";
        }
        $this->configuration = $configuration;
    }

    public function createAeosIdentifier(Code $code, $visitorName = null)
    {
        $user = $code->getCreatedBy() ?? $this->tokenStorage->getToken()->getUser();
        if (!$user) {
            throw new \Exception('Code has no user');
        }
        $template = $code->getTemplate();
        if (!$template) {
            throw new \Exception('Code has no template');
        }
        if (!$template->getAeosId()) {
            throw new \Exception('Template has no aeos id');
        }

        $aeosContactPerson = $this->aeosService->getPerson($user->getAeosId());
        if (!$aeosContactPerson) {
            throw new \Exception('Cannot find AEOS person: '.$user->getAeosId());
        }

        $aeosTemplate = $this->aeosService->getTemplate($template->getAeosId());
        if (!$aeosTemplate) {
            throw new \Exception('Cannot find AEOS template: '.$template->getAeosId());
        }

        if (null === $visitorName) {
            try {
                $visitorName = $this->twig
                    ->createTemplate($this->configuration['vistor_name_template'])
                    ->render([
                                 'code' => $code,
                                 'user' => $user,
                             ]);
            } catch (\Exception $e) {
            }
        }
        $visitorName = trim($visitorName);

        $visitor = $this->aeosService->createVisitor([
            'UnitId' => $aeosContactPerson->UnitId,
            'LastName' => $visitorName,
        ]);

        $this->aeosService->setVerificationState($visitor, false);
        $this->aeosService->createVisit(
            $visitor,
            $aeosContactPerson,
            $code->getStartTime(),
            $code->getEndTime(),
            $aeosTemplate
        );
        $identifier = $this->aeosService->createIdentifier($visitor, $aeosContactPerson);

        $code->setIdentifier($identifier->BadgeNumber);
    }

    /**
     * @param \AppBundle\Entity\Code $code
     */
    public function deleteAeosIdentifier(Code $code)
    {
        $identifier = $this->aeosService->getIdentifierByBadgeNumber($code->getIdentifier());
        $visitor = $identifier ? $this->aeosService->getVisitorByIdentifier($identifier) : null;
        $visit = $visitor ? $this->aeosService->getVisitByVisitor($visitor) : null;

        if ($identifier && !$this->aeosService->isBlocked($identifier)) {
            $this->aeosService->blockIdentifier($identifier);
        }
        if ($visit) {
            $this->aeosService->deleteVisit($visit);
        }
        if ($visitor) {
            $this->aeosService->deleteVisitor($visitor);
        }
    }

    public function userHasAeosId(User $user = null)
    {
        try {
            if (null === $user) {
                $user = $this->tokenStorage->getToken()->getUser();
            }

            return null !== $this->aeosService->getPerson($user->getAeosId());
        } catch (\Exception $ex) {
        }

        return false;
    }
}
