<?php

namespace SSF\Container\Tests\Support;

abstract class TestDependencyAbstract
{
    public function __construct(
        public int $a = 1,
        public int $b = 2,
        public int $c = 3
    ) {}

    public static function make(): static
    {
        return new static;
    }
}