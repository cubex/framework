<?php

namespace Cubex\Attributes;

use Packaged\DiContainer\DependencyInjector;

interface ReturnsAttributeResultInterface
{
  public function result(?DependencyInjector $di): AttributeResult;
}
