<?php

namespace SSF\Container;

use Closure;
use Psr\Container\ContainerInterface;

class Service
{
    private array $cache = [];

    private ContainerInterface $container;

    private mixed $definition;

    private string $name;

    private bool $singleton;

    public function __construct(
        ContainerInterface $container,
        string $name,
        callable|array|null $definition,
        bool $singleton = false
    ) {
        $this->container = $container;
        $this->name = $name;
        $this->definition = $definition;
        $this->singleton = $singleton;
    }

    public function resolve(array $arguments = []): mixed
    {
        return $this->singleton
            ? $this->resolveSingleton($arguments)
            : $this->resolveInstance($arguments);
    }

    private function resolveInstance(array $arguments = []): mixed
    {
        $definition = $this->definition !== null
            ? $this->definition
            : [];

        if (is_object($definition) && !is_callable($definition)) {
            return $definition;
        }

        if ($definition instanceof Closure) {
            return $definition($this->container);
        }

        if (is_array($definition)) {
            $definition = array_replace($definition, $arguments);
        }

        if ($this->isResolvable($definition)) {
            return (new DependencyResolver($this->container, $this->name))->newConcrete($definition);
        }

        return $definition;
    }

    private function resolveSingleton(array $arguments = []): mixed
    {
        $key = sha1(serialize($arguments));

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->resolveInstance($arguments);
    }

    private function isResolvable($definition)
    {
        if (class_exists($this->name) && (is_array($definition) || is_null($definition))) {
            return true;
        }

        if (is_callable($this->name)) {
            return true;
        }

        return false;
    }
}