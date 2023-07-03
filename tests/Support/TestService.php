<?php

namespace SSF\Container\Tests\Support;

class TestService extends TestAbstractService implements TestServiceInterface
{
    public function __construct(
        public TestDependencyInterface $test
    ) {}
}