<?php

/*
 * This file is part of Gæstehåndtering.
 *
 * (c) 2017–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function __construct($environment, $debug)
    {
        // Force the timezone to be UTC.
        date_default_timezone_set('UTC');
        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
            new SunCat\MobileDetectBundle\MobileDetectBundle(),
            new ItkDev\DatabaseBundle\ItkDevDatabaseBundle(),
            new ItkDev\MonologDbBundle\ItkDevMonologDbBundle(),
            new Craue\ConfigBundle\CraueConfigBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new ItkDev\ConfigBundle\ItkDevConfigBundle(),
            new ActionLogBundle\ActionLogBundle(),
            new MockBundle\MockBundle(),
            new AppBundle\AppBundle(),
            new ApiBundle\ApiBundle(),
            new ItkDev\GDPRBundle\ItkDevGDPRBundle(),
            new Superbrave\GdprBundle\SuperbraveGdprBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test', 'acceptance'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
