<?php

namespace SSF\Container\Dependency;

enum AbstractType
{
    case Function;
    case Method;
    case Object;
}
