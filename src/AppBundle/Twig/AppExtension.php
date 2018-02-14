<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Twig;

use AppBundle\Service\AeosHelper;
use AppBundle\Service\Configuration;

class AppExtension extends \Twig_Extension
{
    /** @var \AppBundle\Service\AeosHelper */
    private $aeosHelper;

    /** @var Configuration */
    private $configuration;

    public function __construct(AeosHelper $aeosHelper, Configuration $configuration)
    {
        $this->aeosHelper = $aeosHelper;
        $this->configuration = $configuration;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('user_has_aeos_id', [$this->aeosHelper, 'userHasAeosId']),
            new \Twig_SimpleFunction('get_configuration', [$this->configuration, 'get']),
            new \Twig_SimpleFunction('app_icon', [$this, 'getAppIcon']),
        ];
    }

    public function getAppIcon($size)
    {
        return $this->configuration->get('app_icons.'.$size.'x'.$size);
    }
}
