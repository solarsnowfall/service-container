<?php

namespace SSF\Container\Dependency;

use Psr\Container\ContainerInterface;

interface HandlerInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $abstract
     * @param mixed $definition
     * @param bool $singleton
     */
    public function __construct(
        ContainerInterface $container,
        string $abstract,
        mixed $definition,
        bool $singleton
    );

    /**
     * @return string
     */
    public function getAbstract(): string;

    /**
     * @return mixed
     */
    public function getDefinition(): mixed;

    /**
     * @return bool
     */
    public function isSingleton(): bool;

    /**
     * @return mixed
     */
    public function getConcrete(): mixed;
}