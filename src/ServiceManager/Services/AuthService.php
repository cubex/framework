<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Auth\IAuthedUser;
use Cubex\Auth\IAuthProvider;
use Cubex\Facade\Cookie;
use Packaged\Helpers\ValueAs;

class AuthService extends AbstractServiceProvider
{
  /**
   * @var IAuthProvider
   */
  protected $_authProvider;

  /**
   * @var IAuthedUser
   */
  protected $_authedUser;

  /**
   * Register the service
   *
   * @param array $parameters
   *
   * @return mixed
   */
  public function register(array $parameters = null)
  {
    $this->_authProvider = $this->getCubex()->makeWithCubex(
      $this->getCubex()->getConfiguration()->getItem(
        'auth',
        'provider',
        '\Cubex\Auth\IAuthProvider'
      )
    );
  }

  public function login($username, $password, array $options = null)
  {
    //Call auth provider
    $login = $this->_authProvider->login($username, $password, $options);
    if($login === null)
    {
      throw new \RuntimeException("Unable to login '$username'");
    }

    //Cache the authed user
    $this->_authedUser = $login;

    //Set the cookie for future requests
    $this->_setLoginCookie($login);

    return $login;
  }

  /**
   * Store the login cookie
   *
   * @param IAuthedUser $user
   */
  protected function _setLoginCookie(IAuthedUser $user)
  {
    Cookie::queue(
      Cookie::make(
        $this->getCookieName(),
        $user->serialize(),
        $this->getConfigItem('cookie_time', 2592000),
        null,
        null,
        ValueAs::bool($this->getConfigItem('cookie_secure', false))
      )
    );
  }

  /**
   * Logout the user from the current system
   *
   * @return bool
   */
  public function logout()
  {
    if($this->_authProvider->logout())
    {
      $this->_authedUser = null;
      return true;
    }
    return false;
  }

  /**
   * Returns is the user is logged in
   *
   * @return bool
   */
  public function isLoggedIn()
  {
    return $this->getAuthedUser() !== null;
  }

  /**
   * Name of the cookie to store login information in
   *
   * @return string
   */
  public function getCookieName()
  {
    return $this->getConfigItem('cookie_name', 'cubex_login');
  }

  public function getAuthedUser()
  {
    if($this->_authedUser !== null)
    {
      return $this->_authedUser;
    }

    //Check cookie
    $cookied = Cookie::get($this->getCookieName());
    if($cookied !== null)
    {
      $authedUser = $this->getCubex()->make('\Cubex\Auth\AuthedUser');
      if($authedUser instanceof IAuthedUser)
      {
        try
        {
          $authedUser->unserialize($cookied);
          $this->_authedUser = $authedUser;
          return $this->_authedUser;
        }
        catch(\Exception $e)
        {
          $cookied = null;
        }
      }
    }

    //Check auth provider retrieve
    $serviceUser = $this->_authProvider->retrieveUser();

    if($serviceUser !== null)
    {
      $this->_authedUser = $serviceUser;
      //Set the login cookie when retrieved the user from the auth provider
      //Defaulted to on for speed, but can be disabled within the config
      if(ValueAs::bool($this->getConfigItem('cookie_retriever'), true))
      {
        $this->_setLoginCookie($serviceUser);
      }
    }

    return $this->_authedUser;
  }

  /**
   * @return IAuthProvider|null
   */
  public function getAuthProvider()
  {
    return $this->_authProvider;
  }
}
