<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace MockBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/sms")
 */
class SmsGatewayController extends Controller
{
    /**
     * @Route()
     */
    public function indexAction()
    {
        throw new \Exception(__METHOD__.' not implemented');
    }

    /**
     * @Route("/send")
     * @Method("POST")
     */
    public function sendAction()
    {
        throw new \Exception(__METHOD__.' not implemented');
    }
}
