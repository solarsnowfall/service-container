<?php

namespace SSF\Container;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class DependencyResolver
{
    private readonly ReflectionFunction|ReflectionMethod $reflector;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string|array $abstract
    ) {
        $this->reflector = $this->getReflector();
    }

    public function newConcrete(array $arguments = [])
    {
        $dependencies = $this->getDependencies($arguments);

        if ($this->reflector instanceof ReflectionMethod) {
            if ($this->reflector->isConstructor()) {
                return $this->reflector->getDeclaringClass()->newInstance(...$dependencies);
            } else {
                $arg = is_array($this->abstract) && is_object($this->abstract[0])
                    ? $this->abstract[0] :
                    null;
                array_unshift($dependencies, $arg);
            }
        }

        return $this->reflector->invoke(...$dependencies);
    }

    public function getDependencies(array $arguments = []): array
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
                throw new InvalidArgumentException(sprintf(
                    'Unable to resolve parameter %s of abstract %s',
                    $parameter->name,
                    $this->abstract
                ));
            }
        }

        return $dependencies;
    }

    /**
     * @return ReflectionFunction|ReflectionMethod
     * @throws InvalidArgumentException
     */
    private function getReflector(): ReflectionFunction|ReflectionMethod
    {
        try {

            $abstract = $this->abstract;

            if (is_string($abstract)) {
                if (class_exists($abstract)) {
                    return new ReflectionMethod($abstract, '__construct');
                } elseif (function_exists($abstract)) {
                    return new ReflectionFunction($abstract);
                }
            }

            if (is_string($abstract)) {
                $abstract = explode('::', $abstract);
            }

            if (!is_object($abstract[0]) && method_exists(...$abstract)) {
                $abstract[0] = get_class($abstract[0]);
            }

            if (method_exists(...$abstract)) {
                return new ReflectionMethod(...$abstract);
            }

            throw new InvalidArgumentException(
                'Argument abstract must be a class, method or function name.',
            );

        } catch (Throwable $caught) {
            throw new InvalidArgumentException(
                "Invalid abstract type provided.",
                null,
                $caught
            );
        }
    }

    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        foreach ($this->resolveParameterTypes($parameter) as $type) {
            if ($this->container->has($type->getName())) {
                return $this->container->get($type->getName());
            }
        }

        return null;
    }

    /**
     * @param ReflectionParameter $parameter
     * @return ReflectionNamedType[]
     */
    private function resolveParameterTypes(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();
        /** @var ReflectionNamedType[] $types */
        return false === $type instanceof ReflectionNamedType
            ? $type->getTypes()
            : [$type];
    }


}