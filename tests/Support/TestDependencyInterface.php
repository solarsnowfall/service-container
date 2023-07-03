<?php

namespace SSF\Container\Tests\Support;

interface TestDependencyInterface
{
    public static function make(): static;
}