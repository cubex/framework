<?php

namespace Cubex\Attributes;

use Packaged\DiContainer\DependencyInjector;

abstract class AbstractConditionAttribute
{
  protected string $_class = '';
  protected array $_args = [];

  public function __construct(string $class, array $args = [])
  {
    $this->_class = $class;
    $this->_args = $args;
  }

  public function getClass(): string
  {
    return $this->_class;
  }

  public function result(?DependencyInjector $di): ConditionResult
  {
    if(class_exists($this->_class))
    {
      if($di)
      {
        $obj = $di->resolve($this->_class, ...$this->_args);
      }
      else
      {
        $obj = new $this->_class(...$this->_args);
      }
      
      if($obj instanceof ConditionResult)
      {
        return $obj;
      }
    }
    throw new \RuntimeException("Class {$this->_class} does not exist, or is not a ConditionResult");
  }
}
