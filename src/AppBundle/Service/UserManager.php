<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Doctrine\UserManager as BaseUserManager;
use FOS\UserBundle\Mailer\TwigSwiftMailer;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UserManager extends BaseUserManager
{
    /**
     * @var \FOS\UserBundle\Util\TokenGeneratorInterface
     */
    private $tokenGenerator;

    /** @var \Twig_Environment */
    private $twig;

    /** @var \Symfony\Component\Routing\RouterInterface */
    private $router;

    /** @var TwigSwiftMailer */
    private $userMailer;

    /** @var \Swift_Mailer */
    private $mailer;

    /** @var array */
    private $configuration;

    public function __construct(
        PasswordUpdaterInterface $passwordUpdater,
        CanonicalFieldsUpdater $canonicalFieldsUpdater,
        ObjectManager $om,
        $class,
        TokenGeneratorInterface $tokenGenerator,
        \Twig_Environment $twig,
        RouterInterface $router,
        TwigSwiftMailer $userMailer,
        \Swift_Mailer $mailer,
        array $configuration
    ) {
        parent::__construct($passwordUpdater, $canonicalFieldsUpdater, $om, $class);
        $this->tokenGenerator = $tokenGenerator;
        $this->twig = $twig;
        $this->router = $router;
        $this->userMailer = $userMailer;
        $this->mailer = $mailer;
        $this->configuration = json_decode(json_encode($configuration));
    }

    public function createUser()
    {
        $user = parent::createUser();

        $user->setPlainPassword(uniqid());
        $user->setEnabled(true);

        return $user;
    }

    public function updateUser(UserInterface $user, $andFlush = true)
    {
        $user->setUsername($user->getEmail());

        parent::updateUser($user, $andFlush);
    }

    public function notifyUserCreated(User $user, $andFlush = true)
    {
        if (null === $user->getConfirmationToken()) {
            // @var $tokenGenerator TokenGeneratorInterface
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }
        $user->setPasswordRequestedAt(new \DateTime());
        $this->updateUser($user, $andFlush);

        $message = $this->createUserCreatedMessage($user);
        $this->mailer->send($message);
    }

    public function resetPassword(User $user, $andFlush = true)
    {
        if (null === $user->getConfirmationToken()) {
            // @var $tokenGenerator TokenGeneratorInterface
            $user->setConfirmationToken($this->tokenGenerator->generateToken());
        }
        $user->setPasswordRequestedAt(new \DateTime());
        $this->updateUser($user, $andFlush);

        $this->userMailer->sendResettingEmailMessage($user);
    }

    private function createUserCreatedMessage(UserInterface $user)
    {
        $url = $this->router->generate('fos_user_resetting_reset', [
            'token' => $user->getConfirmationToken(),
            'create' => true,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $config = $this->configuration->user_created;
        $sender = $config->sender;
        $template = $config->user;

        $subject = $this->twig->createTemplate($template->subject)->render([]);
        $parameters = [
            'reset_password_url' => $url,
            'user' => $user,
        ];
        $template->header = $this->twig->createTemplate($template->header)->render($parameters);
        $template->body = $this->twig->createTemplate($template->body)->render($parameters);
        $template->button->text = $this->twig->createTemplate($template->button->text)->render($parameters);
        $template->footer = $this->twig->createTemplate($template->footer)->render($parameters);

        return (new \Swift_Message($subject))
            ->setFrom($sender->email, $sender->name)
            ->setTo($user->getEmail())
            ->setBody($this->twig->render(':Emails:user_created_user.html.twig', [
                'reset_password_url' => $url,
                'header' => $template->header,
                'body' => $template->body,
                'button' => [
                    'url' => $url,
                    'text' => $template->button->text,
                ],
                'footer' => $template->footer,
            ]), 'text/html');
    }
}
