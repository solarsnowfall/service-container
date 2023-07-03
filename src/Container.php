<?php

namespace SSF\Container;

use Psr\Container\ContainerInterface;
use SSF\Container\Dependency\Handler;

class Container implements ContainerInterface
{
    const DEFAULT_CONTEXT = 'default';

    /**
     * @var array
     */
    private array $aliases = [];

    /**
     * @var Container|null
     */
    private static ?Container $container = null;

    /**
     * @var string
     */
    private string $context = self::DEFAULT_CONTEXT;

    /**
     * @var Handler[][]
     */
    private array $handlers = [];

    /**
     *
     */
    public function __construct()
    {
        static::$container = $this;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * @param string $context
     * @return void
     */
    public function setContext(string $context): void
    {
        $this->context = $context;
    }

    public function defaultContext(): void
    {
        $this->context = static::DEFAULT_CONTEXT;
    }

    /**
     * @param string $id
     * @param array $arguments
     * @return mixed
     */
    public function get(string $id, array $arguments = []): mixed
    {
        if (!$this->has($id)) {
            throw new NotFoundException("Service not found: $id");
        }

        return $this->hasAlias($id)
            ? $this->handlers[$this->context][$this->aliases[$id]]->getConcrete($arguments)
            : $this->handlers[$this->context][$id]->getConcrete($arguments);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->handlers[$this->context][$id])
            || $this->hasAlias($id);
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasAlias(string $alias): bool
    {
        return array_key_exists($alias, $this->aliases);
    }

    /**
     * @param string $id
     * @param object|callable|array|null $definition
     * @param bool $singleton
     * @param string|null $context
     * @return void
     */
    public function set(
        string $id,
        mixed $definition = null,
        bool $singleton = false,
        ?string $context = null
    ): void {
        $this->handlers[$context ?? $this->context][$id] = new Handler($this, $id, $definition, $singleton);
    }

    /**
     * @param string $id
     * @param object|callable|array|null $definition
     * @param string|null $context
     * @return void
     */
    public function setSingleton(string $id, object|callable|array $definition = null, ?string $context = null): void
    {
        $this->set($id, $definition, true, $context);
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