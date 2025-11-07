<?php

namespace Cubex\Attributes\Conditional;

use Cubex\Attributes\AttributeResult;
use Cubex\Attributes\ReturnsAttributeResultInterface;
use Packaged\DiContainer\DependencyInjector;

abstract class AbstractConditionReturnsAttribute implements ReturnsAttributeResultInterface
{
  public function __construct(protected string $_class, protected array $_args = [])
  {
  }

  public function getClass(): string
  {
    return $this->_class;
  }

  public function result(?DependencyInjector $di): AttributeResult
  {
    $obj = null;

    if($di)
    {
      $obj = $di->resolve($this->_class, ...$this->_args);
    }
    else if(class_exists($this->_class))
    {
      $obj = new $this->_class(...$this->_args);
    }

    if($obj instanceof AttributeResult)
    {
      return $obj;
    }

    throw new \RuntimeException("Class {$this->_class} does not exist, or is not a ConditionResult");
  }
}
