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
   * Retrieve a cookie
   *
   * @param      $name
   * @param null $default
   *
   * @return mixed
   */
  public static function get($name, $default = null)
  {
    $cubex = self::getFacadeApplication();
    return $cubex['request']->cookies->get($name, $default);
  }
}
