<?php

namespace SSF\Container;

class TestService
{
    public function __construct(
        public readonly TestDependency $test
    ) {}
}