<?php

namespace Hautelook\Tests\Functional;

use Hautelook\TemplatedUriBundle\Tests\Functional\TestCase;

class TemplatedUriRouterTest extends TestCase
{
    /**
     * @dataProvider generateTestCases
     */
    public function testGenerate($route, $param, $expectedRoute, $strictRequirements)
    {
        $kernel = $this->getKernel();

        /** @var $templatedRouter \Symfony\Bundle\FrameworkBundle\Routing\Router */
        $templatedRouter = $kernel->getContainer()->get('hautelook.router.template');

        $templatedRouter->setOption('strict_requirements', $strictRequirements ? true : null);

        $route = $templatedRouter->generate($route, array('foo' => $param));

        $this->assertEquals($expectedRoute, $route);
    }

    public function generateTestCases()
    {
        return array(
            array('route_loose', 'bar', '/test/bar', true),
            array('route_loose', '1234', '/test/1234', true),
            array('route_strict', '1234', '/test/1234', true),
            array('route_loose', 'bar', '/test/bar', false),
            array('route_loose', '1234', '/test/1234', false),
            array('route_strict', '1234', '/test/1234', false),
            array('route_strict', 'bar', '/test/bar', false),
        );
    }

    /**
     * @expectedException \Symfony\Component\Routing\Exception\InvalidParameterException
     */
    public function testGenerateStrictRequirements()
    {
        $kernel = $this->getKernel();

        /** @var $templatedRouter \Symfony\Bundle\FrameworkBundle\Routing\Router */
        $templatedRouter = $kernel->getContainer()->get('hautelook.router.template');

        $route = $templatedRouter->generate('route_strict', array('foo' => 'bar'));

        $this->assertEquals('/test/bar', $route);
    }
}
