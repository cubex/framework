<?php
namespace Cubex\Container;

class DependencyInjector
{
  //Generators
  protected $_generators = [];

  //Shared Instances
  protected $_instances = [];

  public function factory($abstract, callable $generator)
  {
    $this->_generators[$abstract] = $generator;
    return $this;
  }

  public function share($abstract, $instance)
  {
    if($instance !== null)
    {
      $this->_instances[$abstract] = $instance;
    }
    return $this;
  }

  public function removeShared($abstract)
  {
    unset($this->_instances[$abstract]);
    return $this;
  }

  /**
   * @param       $abstract
   * @param array $parameters
   * @param bool  $shared
   *
   * @return mixed
   * @throws \Exception
   */
  public function retrieve($abstract, array $parameters = [], $shared = true)
  {
    if($shared && isset($this->_instances[$abstract]))
    {
      return $this->_instances[$abstract];
    }
    if(isset($this->_generators[$abstract]))
    {
      $instance = $this->_generators[$abstract](...$parameters);
      if($instance !== null)
      {
        if($shared)
        {
          $this->_instances[$abstract] = $instance;
        }
        return $instance;
      }
    }
    throw new \Exception("Unable to retrieve");
  }

  public function hasShared($abstract)
  {
    return isset($this->_instances[$abstract]);
  }

  public function isAvailable($abstract, $shared = null)
  {
    if($shared === false)
    {
      return isset($this->_generators[$abstract]);
    }
    return isset($this->_generators[$abstract]) || isset($this->_instances[$abstract]);
  }
}
