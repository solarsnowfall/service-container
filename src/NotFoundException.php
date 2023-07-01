<?php

namespace SSF\Container;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{}