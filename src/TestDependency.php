<?php

namespace SSF\Container;

class TestDependency
{
    public function __construct(
        public readonly int $a,
        public readonly int $b,
        public readonly int $c
    ) {}
}