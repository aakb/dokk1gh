<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

use GuzzleHttp\Client;

class SmsService implements SmsServiceInterface
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function send($recipient, $message, $countryCode = null)
    {
        try {
            $client = new Client();
            $res = $client->request(
                'POST',
                $this->configuration->get('sms_gateway_url'),
                [
                    'form_params' => [
                        'username' => $this->configuration->get('sms_gateway_username'),
                        'password' => $this->configuration->get('sms_gateway_password'),
                        'countrycode' => $countryCode ?: $this->configuration->get('sms_gateway_default_countrycode'),
                        'number' => $recipient,
                        'message' => $message,
                    ],
                ]
            );

            return true;
        } catch (\Exception $ex) {
            throw $ex;
            // @TODO: Log exception.
        }

        return false;
    }
}
