<?php

namespace SSF\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    const DEFAULT_CONTEXT = 'default';

    /**
     * @var Container|null
     */
    private static ?Container $container = null;

    /**
     * @var string
     */
    private string $context = self::DEFAULT_CONTEXT;

    /**
     * @var Service[][]
     */
    private array $services = [];

    /**
     *
     */
    public function __construct()
    {
        static::$container = $this;
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get(string $id): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service not found: $id");
        }

        return $this->services[$this->context][$id]->resolve();
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->services[$this->context][$id]);
    }

    /**
     * @param string $id
     * @param callable|array|null $definition
     * @param bool $singleton
     * @return void
     */
    public function set(string $id, callable|array|null $definition, bool $singleton = false)
    {
        $this->services[$id] = new Service($this, $id, $definition, $singleton);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public static function make(string $id): mixed
    {
        if (static::$container === null) {
            static::$container = new static;
        }

        return static::$container->get($id);
    }

    /**
     * @param string $id
     * @param callable|array|null $definition
     * @param bool $singleton
     * @return void
     */
    public static function register(string $id, callable|array|null $definition, bool $singleton = false): void
    {
        if (static::$container === null) {
            static::$container = new static;
        }

        static::$container->set($id, $definition, $singleton);
    }
}