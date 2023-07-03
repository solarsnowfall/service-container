<?php

namespace SSF\Container\Dependency;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use SSF\Container\Tests\Support\Gumballs;

class Injector
{
    /**
     * @var AbstractType
     */
    private AbstractType $type;

    /**
     * @var ReflectionFunction|ReflectionMethod
     */
    private readonly ReflectionFunction|ReflectionMethod $reflector;

    /**
     * @param ContainerInterface $container
     * @param array|string $abstract
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array|string $abstract
    ) {
        $this->type = $this->getType();
        $this->reflector = $this->getReflector();
    }

    /**
     * @return AbstractType
     */
    private function getType(): AbstractType
    {
        if (is_string($this->abstract)) {
            if (class_exists($this->abstract)) {
                return AbstractType::Object;
            } elseif (function_exists($this->abstract)) {
                return AbstractType::Function;
            }
        } elseif (method_exists(...$this->abstract)) {
            return AbstractType::Method;
        }

        throw new InvalidArgumentException('Argument $abstract must be a class, method or function name.');
    }

    /**
     * @return ReflectionFunction|ReflectionMethod
     */
    private function getReflector(): ReflectionFunction|ReflectionMethod
    {
        try {
            return match($this->type) {
                AbstractType::Object => new ReflectionMethod($this->abstract, '__construct'),
                AbstractType::Method => new ReflectionMethod(...$this->abstract),
                default => new ReflectionFunction($this->abstract)
            };
        } catch (ReflectionException $exception) {
            throw new InvalidArgumentException('Invalid abstract provided.', null. $exception);
        }
    }

    /**
     * @param array $arguments
     * @return mixed
     */
    public function newConcrete(array $arguments = []): mixed
    {
        return match($this->type) {
            AbstractType::Object => $this->newClassInstance($arguments),
            AbstractType::Method => $this->methodResult($arguments),
            default => $this->functionResult($arguments)
        };
    }

    /**
     * @param array $arguments
     * @return mixed
     */
    private function newClassInstance(array $arguments = []): mixed
    {
        try {
            return (new ReflectionClass($this->abstract))->newInstance(
                ...$this->resolveDependencies($arguments)
            );
        } catch (ReflectionException $caught) {
            throw new RuntimeException("Unable to create class {$this->abstract}", null, $caught);
        }
    }

    /**
     * @param array $arguments
     * @return mixed
     */
    private function methodResult(array $arguments = []): mixed
    {
        try {
            $dependencies = $this->resolveDependencies($arguments);
            $arg = is_array($this->abstract) && is_object($this->abstract[0]) ? $this->abstract[0] : null;
            array_unshift($dependencies, $arg);
            return $this->reflector->invoke(...$dependencies);
        } catch (ReflectionException $caught) {
            throw new RuntimeException("Unable to invoke method", null, $caught);
        }
    }

    /**
     * @param array $arguments
     * @return mixed
     */
    private function functionResult(array $arguments = []): mixed
    {
        try {
            return $this->reflector->invoke(...$this->resolveDependencies($arguments));
        } catch (ReflectionException $caught) {
            throw new RuntimeException("Unable to invoke function", null, $caught);
        }
    }

    /**
     * @param array $arguments
     * @return array
     */
    private function resolveDependencies(array $arguments = []): array
    {
        $dependencies = [];
        $assoc = array_keys($arguments) !== range(0, count($arguments) - 1);

        foreach ($this->reflector->getParameters() as $key => $parameter) {

            $idx = $assoc ? $parameter->getName() : $key;

            if (isset($arguments[$idx])) {
                $dependencies[] = $arguments[$idx];
            } elseif (null !== $value = $this->resolveParameter($parameter)) {
                $dependencies[] = $value;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException("Unable to resolve parameter: " . $parameter->getName());
            }
        }

        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed|null
     */
    private function resolveParameter(ReflectionParameter $parameter)
    {
        /** @var ReflectionNamedType[] $types */
        $types = false === $parameter->getType() instanceof ReflectionNamedType
            ? $parameter->getType()->getTypes()
            : [$parameter->getType()];

        foreach ($types as $type) {
            if (null !== $found = $this->searchContainerForType($type)) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param ReflectionNamedType $type
     * @return mixed
     */
    private function searchContainerForType(ReflectionNamedType $type): mixed
    {
        try {

            if ($this->container->has($type->getName())) {
                return $this->container->get($type->getName());
            }

            if (interface_exists($type->getName())) {
                return $this->findImplementing(new ReflectionClass($type->getName()));
            }

            if (class_exists($type->getName())) {
                return $this->findExtending(new ReflectionClass($type->getName()));
            }

            return null;

        } catch (ReflectionException $exception) {
            return null;
        }
    }

    /**
     * @param ReflectionClass $typeClass
     * @return mixed|null
     */
    private function findExtending(ReflectionClass $typeClass)
    {
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, $typeClass->getName()) && $this->container->has($class)) {
                return $this->container->get($class);
            }
        }

        return null;
    }

    /**
     * @param ReflectionClass $typeClass
     * @return mixed|null
     */
    private function findImplementing(ReflectionClass $typeClass)
    {
        foreach (get_declared_classes() as $class) {

            if (!$this->container->has($class)) {
                continue;
            }

            foreach (class_implements($class) ?? [] as $interface) {
                if ($interface === $typeClass->getName()) {
                    return $this->container->get($class);
                }
            }
        }

        return null;
    }
}