<?php
namespace Cubex\Container;

use Packaged\Helpers\Objects;
use function class_exists;
use function is_scalar;

class DependencyInjector
{
  const MODE_IMMUTABLE = 'i';
  const MODE_MUTABLE = 'm';

  //Generators
  protected $_factories = [];

  //Shared Instances
  protected $_instances = [];

  public function factory($abstract, callable $generator, $mode = self::MODE_MUTABLE)
  {
    $this->_factories[$abstract] = ['generator' => $generator, 'mode' => $mode];
    return $this;
  }

  public function removeFactory($abstract)
  {
    unset($this->_factories[$abstract]);
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
      return $this->_buildInstance($this->_instances[$abstract]['instance'], $parameters);
    }
    if(isset($this->_factories[$abstract]))
    {
      $instance = $this->_factories[$abstract]['generator'](...$parameters);
      if($instance !== null)
      {
        if($shared)
        {
          $this->share($abstract, $instance, $this->_factories[$abstract]['mode']);
        }
        return $instance;
      }
    }
    throw new \Exception("Unable to retrieve");
  }

  /**
   * @param       $instance
   * @param array $parameters
   *
   * @return mixed
   * @throws \ReflectionException
   */
  protected function _buildInstance($instance, array $parameters = [])
  {
    if(is_scalar($instance) && class_exists($instance))
    {
      return Objects::create($instance, $parameters);
    }
    return $instance;
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

  /**
   * Check to see if an abstract has a shared instance already bound
   *
   * @param             $abstract
   * @param string|null $checkMode optionally check the correct mode is also set
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
