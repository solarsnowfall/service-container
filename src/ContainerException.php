<?php

namespace SSF\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{}