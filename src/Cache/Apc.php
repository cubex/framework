<?php
namespace Cubex\Cache;

class Apc
{
  public static function retrieve(string $key, callable $callback, int $ttl = 3600)
  {
    $exists = null;
    $value = apcu_fetch($key, $exists);
    if($exists !== true)
    {
      $value = $callback();
      apcu_store($key, $value, $ttl);
    }
    return $value;
  }

  public static function delete(string $key)
  {
    return apcu_delete($key);
  }
}
