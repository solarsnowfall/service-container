<?php

namespace SSF\Container\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SSF\Container\Container;
use SSF\Container\Dependency\Handler;

class DependencyHandlerTest extends TestCase
{
    protected Container $container;

    public function setUp(): void
    {
        $this->container = new Container();
        parent::setUp();
    }

    public static function dataProvider()
    {
        $container = new Container();

        return [
            'Primitive' => [
                'container' => $container,
                'abstract' => 'test_primitive',
                'definition' => 'a'
            ]
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param ContainerInterface $container
     * @param string $abstract
     * @param mixed $definition
     * @param bool $singleton
     * @return void
     */
    public function testHandlesTypes(
        ContainerInterface $container,
        string $abstract,
        mixed $definition,
        bool $singleton = false,
        array $arguments = []
    ) {
        $handler = new Handler($container, $abstract, $definition, $singleton);
        $concrete = $handler->getConcrete();
        $this->assertEquals($definition, $concrete);
    }
}