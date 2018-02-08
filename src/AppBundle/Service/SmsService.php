<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

class SmsService implements SmsServiceInterface
{
    public function send($recipient, $message)
    {
        return true;
    }
}
