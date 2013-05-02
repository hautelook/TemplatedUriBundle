<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hautelook\TemplatedUriBundle\Tests\Routing\Generator;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Baldur Rensch <baldur.rensch@hautelook.com>
 */
class Rfc6570GeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestPlaceholderData
     */
    public function testPlaceholder($expected, $parameters)
    {
        $routes = new RouteCollection();

        $routes->add('foo', new Route(
            '/foo/{foo}/',
            array(
                'foo'    => '123',
            ),
            array(
            )
        ));

        $container = $this->getServiceContainer($routes);

        $router = new Router($container, 'foo', array(
            'generator_class' => 'Hautelook\\TemplatedUriBundle\\Routing\\Generator\\Rfc6570Generator',
        ));

        $this->assertEquals($expected, $router->generate('foo', $parameters));
    }

    public function getTestPlaceholderData()
    {
        return array(
            array('/foo/foobar/?{&bar}', array('foo' => 'foobar', 'bar' => 'barbar')),
            array('/foo/foobar/?{&bar%5B%5D*}', array('foo' => 'foobar', 'bar' => array())),
            array('/foo/{placeholder}/?{&bar}', array('foo' => '{placeholder}', 'bar' => 'barbar')),
        );
    }

    private function getServiceContainer(RouteCollection $routes)
    {
        $loader = $this->getMock('Symfony\Component\Config\Loader\LoaderInterface');
        $loader->expects($this->any())
            ->method('load')
            ->will($this->returnValue($routes))
        ;

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($loader))
        ;

        return $container;
    }
}
