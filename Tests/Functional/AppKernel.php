<?php

namespace Hautelook\TemplatedUriBundle\Tests\Functional;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Hautelook\TemplatedUriBundle\HautelookTemplatedUriBundle(),
        );
    }

    public function getCacheDir()
    {
        return sys_get_temp_dir().'/TemplatedUriBundle/';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
