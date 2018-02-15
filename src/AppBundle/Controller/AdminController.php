<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Controller;

use AppBundle\Service\TemplateManager;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Gedmo\Blameable\Blameable;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AdminController extends BaseAdminController
{
    /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface */
    protected $tokenStorage;

    /** @var \AppBundle\Service\TemplateManager */
    protected $templateManager;

    /** @var \Twig_Environment */
    protected $twig;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TemplateManager $templateManager,
        \Twig_Environment $twig
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->templateManager = $templateManager;
        $this->twig = $twig;
    }

    protected function createListQueryBuilder($entityClass, $sortDirection, $sortField = null, $dqlFilter = null)
    {
        $this->limitByUser($dqlFilter, $entityClass, 'entity');

        return parent::createListQueryBuilder($entityClass, $sortDirection, $sortField, $dqlFilter);
    }

    protected function createSearchQueryBuilder(
        $entityClass,
        $searchQuery,
        array $searchableFields,
        $sortField = null,
        $sortDirection = null,
        $dqlFilter = null
    ) {
        // Use only list fields in search query.
        $this->entity['search']['fields'] = array_filter($this->entity['search']['fields'], function ($key) {
            return isset($this->entity['list']['fields'][$key]);
        }, ARRAY_FILTER_USE_KEY);

        $this->limitByUser($dqlFilter, $entityClass, 'entity');

        return parent::createSearchQueryBuilder(
            $entityClass,
            $searchQuery,
            $searchableFields,
            $sortField,
            $sortDirection,
            $dqlFilter
        );
    }

    protected function showSuccess(string $message, array $parameters = [])
    {
        $this->showMessage('success', $message, $parameters);
    }

    protected function showInfo(string $message, array $parameters = [])
    {
        $this->showMessage('info', $message, $parameters);
    }

    protected function showWarning(string $message, array $parameters = [])
    {
        $this->showMessage('warning', $message, $parameters);
    }

    protected function showError(string $message, array $parameters = [])
    {
        $this->showMessage('error', $message, $parameters);
    }

    protected function showMessage(string $type, string $message, array $parameters = [])
    {
        $translator = $this->get('translator');

        // If message looks like a twig template filename we render it as a template.
        if (preg_match('/\.(html|txt)\.twig$/', $message)) {
            $message = $this->twig->render($message, $parameters);
            $parameters = [];
        }

        $this->addFlash($type, $translator->trans($message, $parameters));
    }

    private function limitByUser(string &$dqlFilter = null, string $entityClass, string $alias)
    {
        $limitByUserFilter = $this->getLimitByUserFilter($entityClass, $alias);
        if ($limitByUserFilter) {
            if ($dqlFilter) {
                $dqlFilter .= ' and '.$limitByUserFilter;
            } else {
                $dqlFilter = $limitByUserFilter;
            }
        }
    }

    private function getLimitByUserFilter(string $entityClass, string $alias)
    {
        // instanceof does not work with string as first operand.
        if (!is_subclass_of($entityClass, Blameable::class)) {
            return null;
        }

        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->get('security.authorization_checker');
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            $user = $this->tokenStorage->getToken()->getUser();

            return $alias.'.createdBy = '.$user->getId();
        }

        return null;
    }
}
