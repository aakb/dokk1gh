<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Guest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class AppController.
 *
 * @Route("/app/{guest}")
 */
class AppController extends Controller
{
    /**
     * @Route("", name="app_code")
     */
    public function codeAction(Guest $guest)
    {
        return $this->render('app/code/index.html.twig');
    }

    /**
     * @Route("/card", name="app_card")
     */
    public function cardAction()
    {
        return $this->render('app/card/index.html.twig');
    }

    /**
     * @Route("/about", name="app_about")
     */
    public function aboutAction()
    {
        return $this->render('app/about/index.html.twig');
    }
}
