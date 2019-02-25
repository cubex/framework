<?php
namespace Cubex\Container;

class DependencyInjector
{
  const MODE_IMMUTABLE = 'i';
  const MODE_MUTABLE = 'm';

  //Generators
  protected $_factories = [];

  //Shared Instances
  protected $_instances = [];

  public function factory($abstract, callable $generator)
  {
    $this->_factories[$abstract] = $generator;
    return $this;
  }

  public function removeFactory($abstract)
  {
    unset($this->_factories[$abstract]);
    return $this;
  }

  public function share($abstract, $instance, $mode = self::MODE_MUTABLE)
  {
    if($instance !== null)
    {
      if(!isset($this->_instances[$abstract]) || $this->_instances[$abstract]['mode'] !== self::MODE_IMMUTABLE)
      {
        $this->_instances[$abstract] = ['instance' => $instance, 'mode' => $mode];
      }
    }
    return $this;
  }

  public function removeShared($abstract)
  {
    if(isset($this->_instances[$abstract]) && $this->_instances[$abstract]['mode'] !== self::MODE_IMMUTABLE)
    {
      unset($this->_instances[$abstract]);
    }
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
      return $this->_instances[$abstract]['instance'];
    }
    if(isset($this->_factories[$abstract]))
    {
      $instance = $this->_factories[$abstract](...$parameters);
      if($instance !== null)
      {
        if($shared)
        {
          $this->share($abstract, $instance);
        }
        return $instance;
      }
    }
    throw new \Exception("Unable to retrieve");
  }

  /**
   * Check to see if an abstract has a shared instance already bound
   *
   * @param      $abstract
   * @param null $checkMode optionally check the correct mode is also set
   *
   * @return bool
   */
  public function hasShared($abstract, $checkMode = null)
  {
    $exists = isset($this->_instances[$abstract]);
    return !$exists || $checkMode === null ? $exists : $this->_instances[$abstract]['mode'] === $checkMode;
  }

  public function isAvailable($abstract, $shared = null)
  {
    if($shared === false)
    {
      return isset($this->_factories[$abstract]);
    }
    return isset($this->_factories[$abstract]) || isset($this->_instances[$abstract]);
  }
}
