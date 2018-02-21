<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Service;

use Craue\ConfigBundle\Util\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class Configuration
{
    /** @var Config */
    private $config;

    /** @var ContainerInterface */
    private $container;

    public function __construct(Config $config, ContainerInterface $container)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function get($path, $defaultValue = null)
    {
        $settings = $this->config->all();
        if (array_key_exists($path, $settings)) {
            return $this->config->get($path);
        }

        $parameters = $this->container->getParameterBag();
        if ($parameters->has($path)) {
            $config = $parameters->get($path);
        } else {
            $steps = explode('.', $path);
            foreach ($steps as $index => $step) {
                if (0 === $index) {
                    $config = $parameters->get($step);
                } elseif (!isset($config[$step])) {
                    throw new ParameterNotFoundException(implode('.', array_slice($steps, 0, $index + 1)));
                } else {
                    $config = $config[$step];
                }
            }
        }

        if ('code.config' === $path) {
            $config['daysDisabled'] = array_values(
                array_diff(
                    range(0, 6),
                    isset($config['daysEnabled']) ? $config['daysEnabled'] : []
                )
            );
        }

        return $config;
    }
}
