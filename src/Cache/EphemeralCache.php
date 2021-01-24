<?php
namespace Cubex\Cache;

class EphemeralCache
{
  protected static $inst;

  public static function instance(): self
  {
    if(!isset(static::$inst))
    {
      static::$inst = new static();
    }
    return static::$inst;
  }

  protected $_cache = [];

  /**
   * Retrieve an item from the cache, if it cannot be found, store the value of the producer into the cache
   *
   * @param          $key
   * @param callable $producer
   * @param int|null $ttl Time in seconds until the cache will expire
   *
   * @return mixed
   */
  public function retrieve($key, callable $producer, int $ttl = null)
  {
    if(!$this->has($key))
    {
      $this->set($key, $producer($key), $ttl);
    }
    return $this->get($key);
  }

  public function has($key)
  {
    return isset($this->_cache[$key]) && ($this->_cache[$key]['t'] === null || $this->_cache[$key]['t'] >= time());
  }

  public function set($key, $value, int $ttl = null)
  {
    $this->_cache[$key] = ['v' => $value, 't' => time() + $ttl];
    return $this;
  }

  /**
   * Lookup a value from ephemeral cache, returning a default value if unavailable
   *
   * @param $key
   * @param $default
   *
   * @return mixed
   */
  public function get($key, $default = null)
  {
    return $this->has($key) && isset($this->_cache[$key]['v']) ? $this->_cache[$key]['v'] : $default;
  }

  public function delete($key)
  {
    unset($this->_cache[$key]);
    return $this;
  }
}
