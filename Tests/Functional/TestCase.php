<?php

namespace Hautelook\TemplatedUriBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

abstract class TestCase extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        $env = @$options['environment'] ?: 'test';

        return new AppKernel($env, true);
    }

    protected static function initializeKernel(array $options = array())
    {
        if (null !== static::$kernel) {
            return;
        }

        static::$kernel = static::createKernel($options);
        static::$kernel->boot();
    }

    protected static function getKernel()
    {
        static::initializeKernel();

        return static::$kernel;
    }

    protected function setUp()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/TemplatedUriBundle/');
    }

    protected function tearDown()
    {
        static::$kernel = null;
    }
} 
