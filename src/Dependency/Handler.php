<?php

namespace SSF\Container\Dependency;

use Psr\Container\ContainerInterface;

class Handler implements HandlerInterface
{
    protected readonly ContainerInterface $container;

    protected readonly string $abstract;

    protected readonly mixed $definition;

    protected readonly bool $singleton;

    private static array $cache = [];

    public function __construct(ContainerInterface $container, string $abstract, mixed $definition, bool $singleton)
    {
        $this->container = $container;
        $this->abstract = $abstract;
        $this->definition = $definition;
        $this->singleton = $singleton;
    }

    /**
     * @return string
     */
    public function getAbstract(): string
    {
        return $this->abstract;
    }

    /**
     * @return mixed
     */
    public function getDefinition(): mixed
    {
        return $this->definition;
    }

    /**
     * @return bool
     */
    public function isSingleton(): bool
    {
        return $this->singleton;
    }

    public function getConcrete(array $arguments = []): mixed
    {
        if ($this->isCached()) {
            return $this->getCached();
        }

        if (is_callable($this->definition)) {
            if ($this->definition instanceof \Closure) {
                $arguments = [$this->container];
            }
            $concrete = call_user_func($this->definition, ...$arguments);
        } elseif (class_exists($this->abstract)) {
            $concrete = $this->resolveObject($arguments);
        } elseif (is_array($this->definition)) {
            $concrete = array_replace($this->definition, $arguments);
        } else {
            $concrete = $this->definition;
        }

        if ($this->isSingleton()) {
            $this->cache($concrete);
        }

        return $concrete;
    }

    /**
     * @return bool
     */
    protected function isCached(): bool
    {
        return $this->singleton
            && isset(static::$cache[static::class][$this->abstract]);
    }

    /**
     * @param mixed|null $default
     * @return mixed
     */
    protected function getCached(mixed $default = null): mixed
    {
        return static::$cache[static::class][$this->abstract] ?? $default;
    }

    /**
     * @param mixed $concrete
     * @return void
     */
    protected function cache(mixed $concrete): void
    {
        static::$cache[static::class][$this->abstract] = $concrete;
    }

    private function resolveObject(array $arguments = []): mixed
    {
        if (is_object($this->definition)) {
            return $this->mapArgumentsToObject(clone $this->definition, $arguments);
        }

        return $this->buildObject($arguments);
    }

    private function buildObject(array $arguments = []): mixed
    {
        if (is_array($this->definition)) {
            $arguments = array_replace($this->definition, $arguments);
        }

        return (new Injector($this->container, $this->abstract))->newConcrete($arguments);
    }

    private function mapArgumentsToObject(object $object, array $arguments)
    {
        foreach (array_keys(get_object_vars($object)) as $key => $name) {
            if (isset($arguments[$name]) || isset($arguments[$key])) {
                $object->$name = $arguments[$name] ?? $arguments[$key];
            }
        }

        return $object;
    }
}