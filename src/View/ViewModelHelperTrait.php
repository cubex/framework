<?php
namespace Cubex\View;

trait ViewModelHelperTrait
{
  /**
   * Check to see if an offset exists and is not empty
   *
   * @param $key
   *
   * @return bool
   */
  public function has($key)
  {
    if(isset($this->{'_' . $key}))
    {
      return !empty($this->{'_' . $key});
    }
    return false;
  }

  /**
   * Get a count of $offset
   *
   * @param $key
   *
   * @return int|void
   */
  public function count($key)
  {
    if(isset($this->{'_' . $key}))
    {
      return count($this->{'_' . $key});
    }
    return 0;
  }

  /**
   * Retrieve a value from the data
   *
   * @param      $key
   * @param null $default
   *
   * @return null
   */
  public function get($key, $default = null)
  {
    if(isset($this->{'_' . $key}))
    {
      return $this->{'_' . $key};
    }
    return $default;
  }

  public function __call($method, $args)
  {
    if(starts_with($method, 'has'))
    {
      return $this->has(lcfirst(substr($method, 3)));
    }
    else if(starts_with($method, 'get'))
    {
      return $this->get(
        lcfirst(substr($method, 3)),
        isset($args[0]) ? $args[0] : null
      );
    }
    else if(starts_with($method, 'count'))
    {
      return $this->count(lcfirst(substr($method, 5)));
    }

    throw new \Exception("Unsupported method $method");
  }
}
