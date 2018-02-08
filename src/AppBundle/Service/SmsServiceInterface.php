<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

interface SmsServiceInterface
{
    /**
     * Send an SMS message to the specified recipient (mobile phone number).
     *
     * @param $recipient
     * @param $message
     *
     * @return bool
     */
    public function send($recipient, $message);
}
