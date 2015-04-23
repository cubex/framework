<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Auth\IAuthedUser;
use Cubex\Auth\IAuthProvider;
use Cubex\Facade\Cookie;
use Cubex\Http\Request;
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

  /**
   * @param       $username
   * @param       $password
   * @param array $options
   *
   * @return IAuthedUser|null
   */
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
    $request = $this->getCubex()->make('request');
    $domain = $this->_getCookieDomain();
    if($request instanceof Request)
    {
      $domain = $this->_getCookieDomain($request);
    }

    Cookie::queue(
      Cookie::make(
        $this->getCookieName(),
        $user->serialize(),
        $this->getCubex()->getConfiguration()->getItem(
          'auth',
          'cookie_time',
          2592000
        ),
        null,
        $domain,
        ValueAs::bool(
          $this->getCubex()->getConfiguration()->getItem(
            'auth',
            'cookie_secure',
            false
          )
        )
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

      $request = $this->getCubex()->make('request');
      $domain = $this->_getCookieDomain();
      if($request instanceof Request)
      {
        $domain = $this->_getCookieDomain($request);
      }

      Cookie::delete($this->getCookieName(), null, $domain);
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
    try
    {
      return $this->getAuthedUser() !== null;
    }
    catch(\Exception $e)
    {
      return false;
    }
  }

  /**
   * Name of the cookie to store login information in
   *
   * @return string
   */
  public function getCookieName()
  {
    return $this->getCubex()->getConfiguration()->getItem(
      'auth',
      'cookie_name',
      'cubex_login'
    );
  }

  /**
   * @return IAuthedUser
   */
  public function getAuthedUser()
  {
    if($this->_authedUser !== null)
    {
      return $this->_authedUser;
    }

    //Check cookie
    $cookieUser = $this->getCookieUser(false);
    if($cookieUser !== null)
    {
      $this->_authedUser = $cookieUser;
      return $this->_authedUser;
    }

    //Check auth provider retrieve
    $serviceUser = $this->_authProvider->retrieveUser();

    if($serviceUser !== null)
    {
      $this->_authedUser = $serviceUser;
      //Set the login cookie when retrieved the user from the auth provider
      //Defaulted to on for speed, but can be disabled within the config
      if(ValueAs::bool(
        $this->getCubex()->getConfiguration()->getItem(
          'auth',
          'cookie_retriever'
        ),
        true
      )
      )
      {
        $this->_setLoginCookie($serviceUser);
      }
    }

    return $this->_authedUser;
  }

  /**
   * Retrieve the user from the cookie
   *
   * @param bool $checkQueue
   *
   * @return IAuthedUser|null
   */
  public function getCookieUser($checkQueue = true)
  {
    $cookied = Cookie::get($this->getCookieName(), null, $checkQueue);
    if($cookied !== null)
    {
      $authedUser = $this->getCubex()->make('\Cubex\Auth\AuthedUser');
      if($authedUser instanceof IAuthedUser)
      {
        try
        {
          $authedUser->unserialize($cookied);
          return $authedUser;
        }
        catch(\Exception $e)
        {
        }
      }
    }
    return null;
  }

  /**
   * Update the cached authed user in cookie and auth service
   *
   * This will not make any changes to the source of your authed user e.g. db
   *
   * @param IAuthedUser $user
   *
   * @return bool
   */
  public function updateAuthedUser(IAuthedUser $user)
  {
    $this->_authedUser = $user;
    $this->_setLoginCookie($user);
    return true;
  }

  /**
   * @return IAuthProvider|null
   */
  public function getAuthProvider()
  {
    return $this->_authProvider;
  }

  public function setAuthProvider(IAuthProvider $provider)
  {
    $this->_authProvider = $provider;
    return $this;
  }

  /**
   * Send forgotten password
   *
   * @param       $username
   * @param array $options
   *
   * @return bool
   */
  public function forgottenPassword($username, array $options = null)
  {
    return $this->_authProvider->forgottenPassword($username, $options);
  }

  public function _getCookieDomain(Request $request = null)
  {
    if($request === null)
    {
      return $this->getCubex()->getConfiguration()->getItem(
        'auth',
        'cookie_domain'
      );
    }

    return $request->urlSprintf(
      $this->getCubex()->getConfiguration()->getItem(
        'auth',
        'cookie_domain_format',
        "%d.%t"
      )
    );
  }
}
