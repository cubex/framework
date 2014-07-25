<?php
namespace Cubex\Facade;

use Cubex\Auth\IAuthedUser;
use Cubex\ServiceManager\Services\AuthService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AuthService getFacadeRoot()
 */
class Auth extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'auth';
  }

  /**
   * Logout the user from the current system
   *
   * @return bool
   */
  public static function logout()
  {
    return self::getFacadeRoot()->logout();
  }

  /**
   * Returns is the user is logged in
   *
   * @return bool
   */
  public static function isLoggedIn()
  {
    return self::getFacadeRoot()->isLoggedIn();
  }

  /**
   * @return \Cubex\Auth\IAuthedUser
   *
   * @throws \Exception
   * @throws \RuntimeException
   */
  public static function getAuthedUser()
  {
    return self::getFacadeRoot()->getAuthedUser();
  }

  /**
   * Update the cached authed user in cookie and auth service
   *
   * This will not make any changes to the source of your authed user e.g. db
   *
   * @param IAuthedUser $user
   *
   * @return bool
   *
   * @throws \Exception
   * @throws \RuntimeException
   */
  public static function updateAuthedUser(IAuthedUser $user)
  {
    return self::getFacadeRoot()->updateAuthedUser($user);
  }

  /**
   * @param       $username
   * @param       $password
   * @param array $options
   *
   * @return \Cubex\Auth\IAuthedUser
   *
   * @throws \Exception
   * @throws \RuntimeException
   */
  public static function login($username, $password, array $options = null)
  {
    return self::getFacadeRoot()->login($username, $password, $options);
  }

  public static function forgottenPassword($username, array $options = null)
  {
    return self::getFacadeRoot()->forgottenPassword($username, $options);
  }
}
