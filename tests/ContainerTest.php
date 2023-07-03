<?php

namespace SSF\Container\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Test;
use SSF\Container\Container;
use SSF\Container\NotFoundException;
use SSF\Container\Tests\Support\TestDependency;
use SSF\Container\Tests\Support\TestDependencyInterface;
use SSF\Container\Tests\Support\TestService;
use SSF\Container\Tests\Support\TestServiceInterface;

class ContainerTest extends TestCase
{
    protected Container $container;

    public function setUp(): void
    {
        $this->container = new Container();
    }

    public function testThrowsExceptionWhenIdNotSet()
    {
        $exception = null;

        try {
            $this->container->get('asdf');
        } catch (\Throwable $caught) {
            $exception = $caught;
        }

        $this->assertInstanceOf(NotFoundException::class, $exception);
    }

    public function returnsTrueWhenHas()
    {
        $this->container->set('test', 'a');
        $this->assertEquals(true, $this->container->has('test'));
    }

    public function returnsFalseWhenHasNot()
    {
        $this->assertEquals(false, $this->container->has('test'));
    }

    public static function typeProvider(): array
    {
        return [
            ['Null', null, null],
            ['IsInt', 1, 1],
            ['IsString', 'a', 'a'],
            ['IsBool', true, true],
            ['IsFloat', 1.1, 1.1],
            ['IsArray', [1, 2, 3], [1, 2, 3]],
            ['IsObject', (object)['a' => 1], (object)['a' => 1]]
        ];
    }


    /**
     * @dataProvider typeProvider
     * @param string $id
     * @param mixed $definition
     * @param mixed $expected
     * @return void
     */
    public function testSetWithTypes(string $id, mixed $definition, mixed $expected)
    {
        $this->container->set($id, $definition);
        $result = $this->container->get($id);
        $this->assertEquals($expected, $result);
        call_user_func([$this, "assert$id"], $result);
    }

    public static function classDefinitionProvider(): array
    {
        return [
            'Null' => [TestDependency::class, null, 1],
            'Array' => [TestDependency::class, [3, 2, 1], 3],
            'Object' => [TestDependency::class, new TestDependency, 1],
            'Closure' => [TestDependency::class, fn() => new TestDependency, 1],
            'StaticMethod' => [TestDependency::class, [TestDependency::class, 'make'], 1]
        ];
    }

    /**
     * @dataProvider classDefinitionProvider
     */
    public function testSetClassDefinition(string $id, mixed $definition, int $a)
    {
        $this->container->set($id, $definition);
        $obj = $this->container->get($id);
        $this->assertInstanceOf($id, $obj);
        $this->assertEquals($a, $obj->a);
    }


    public static function argumentsProvider(): array
    {
        return [
            'ClassWithNull' => [TestDependency::class, null, [3, 2, 1], 3],
            'ClassWithArray' => [TestDependency::class, [3, 2, 1], [4, 5, 6], 4],
            'ClassWithObject' => [TestDependency::class, new TestDependency, [3, 2, 1], 3]
        ];
    }

    /**
     * @dataProvider argumentsProvider
     */
    public function testGetWithArguments(string $id, mixed $definition, array $arguments, int $a)
    {
        $this->container->set(TestDependency::class, $definition);
        $obj = $this->container->get($id, $arguments);
        $this->assertInstanceOf($id, $obj);
        $this->assertEquals($a, $obj->a);
    }

    public function testWithPrior()
    {
        $this->container->set(TestDependency::class, [1, 2, 3]);
        $this->container->set(TestService::class);
        $result = $this->container->get(TestService::class);
        $this->assertInstanceOf(TestDependency::class, $result->test);
    }
}