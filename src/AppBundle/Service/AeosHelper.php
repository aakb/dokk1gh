<?php

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

    public function __construct(AeosService $aeosService, TokenStorageInterface $tokenStorage)
    {
        $this->aeosService = $aeosService;
        $this->tokenStorage = $tokenStorage;
    }

    public function createAeosIdentifier(Code $code)
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

        $visitorName = 'dokk1gh: code: #'.$code->getId().'; '.(new \DateTime())->format(\DateTime::W3C);

        $visitor = $this->aeosService->createVisitor([
            'UnitId' => $aeosContactPerson->UnitId,
            'LastName' => $visitorName,
        ]);

        $this->aeosService->setVerificationState($visitor, false);
        $this->aeosService->createVisit($visitor, $aeosContactPerson, $code->getStartTime(), $code->getEndTime(), $aeosTemplate);
        $identifier = $this->aeosService->createIdentifier($visitor, $aeosContactPerson);

        $code->setIdentifier($identifier->BadgeNumber);
    }

    public function deleteAeosIdentifier(Code $code)
    {
        $identifier = $this->aeosService->getIdentifierByBadgeNumber($code->getIdentifier());
        $visitor = $identifier ? $this->aeosService->getVisitorByIdentifier($identifier) : null;
        $visit = $visitor ? $this->aeosService->getVisitByVisitor($visitor) : null;

        if ($identifier && !$this->aeosService->isDeleted($identifier)) {
            $this->aeosService->deleteIdentifier($identifier);
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
            if ($user === null) {
                $user = $this->tokenStorage->getToken()->getUser();
            }

            return $this->aeosService->getPerson($user->getAeosId()) !== null;
        } catch (\Exception $ex) {
        }

        return false;
    }
}
