<?php
namespace Cubex\Facade;

use Cubex\Cubex;
use Illuminate\Cookie\CookieJar;
use Illuminate\Support\Facades\Facade;
use \Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Class Log
 * @method static Cubex getFacadeApplication()
 */
class Cookie extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'cookie';
  }

  /**
   * @return CookieJar
   */
  public static function getJar()
  {
    return self::getFacadeRoot();
  }

  /**
   * Queue a cookie to be set
   *
   * @param SymfonyCookie $cookie
   */
  public static function queue(SymfonyCookie $cookie)
  {
    self::getJar()->queue($cookie);
  }

  /**
   * Create a new cookie instance.
   *
   * @param  string $name
   * @param  string $value
   * @param  int    $minutes
   * @param  string $path
   * @param  string $domain
   * @param  bool   $secure
   * @param  bool   $httpOnly
   *
   * @return \Symfony\Component\HttpFoundation\Cookie
   */
  public static function make(
    $name, $value, $minutes = 0, $path = null, $domain = null, $secure = false,
    $httpOnly = true
  )
  {
    return self::getJar()->make(
      $name,
      $value,
      $minutes,
      $path,
      $domain,
      $secure,
      $httpOnly
    );
  }

  /**
   * Return an expired cookie to queue
   *
   * @param      $name
   * @param null $path
   * @param null $domain
   *
   * @return SymfonyCookie
   */
  public static function forget($name, $path = null, $domain = null)
  {
    return self::getJar()->forget($name, $path, $domain);
  }

  /**
   * Forget and queue a cookie
   *
   * @param      $name
   * @param null $path
   * @param null $domain
   */
  public static function delete($name, $path = null, $domain = null)
  {
    self::getJar()->queue(self::getJar()->forget($name, $path, $domain));
  }

  /**
   * Retrieve a cookie
   *
   * @param string $name
   * @param null   $default
   * @param bool   $checkQueued check the pending cookie queue
   *
   * @return mixed
   */
  public static function get($name, $default = null, $checkQueued = true)
  {
    $cubex  = self::getFacadeApplication();
    $cookie = $cubex['request']->cookies->get($name, null);
    if($cookie === null && $checkQueued)
    {
      $queue = self::getJar()->getQueuedCookies();
      if(isset($queue[$name]))
      {
        /**
         * @var $queue \Symfony\Component\HttpFoundation\Cookie[]
         */
        $cookie = $queue[$name]->getValue();
      }
    }
    return $cookie === null ? $default : $cookie;
  }
}
