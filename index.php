<?php

include 'vendor/autoload.php';

use SSF\Container\Container;
use SSF\Container\TestService;
use SSF\Container\TestDependency;

$container = new Container();
$container->set(TestDependency::class, [1, 2, 3]);
$container->set(TestService::class, null);
var_dump($container->get(TestService::class));