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
    $domain  = null;
    if($request instanceof Request)
    {
      $domain = $request->domain() . '.' . $request->tld();
    }

    Cookie::queue(
      Cookie::make(
        $this->getCookieName(),
        $user->serialize(),
        $this->getConfigItem('cookie_time', 2592000),
        null,
        $domain,
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

      $request = $this->getCubex()->make('request');
      $domain  = null;
      if($request instanceof Request)
      {
        $domain = $request->domain() . '.' . $request->tld();
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
    return $this->getConfigItem('cookie_name', 'cubex_login');
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
    $cookieUser = $this->getCookieUser();
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
      if(ValueAs::bool($this->getConfigItem('cookie_retriever'), true))
      {
        $this->_setLoginCookie($serviceUser);
      }
    }

    return $this->_authedUser;
  }

  public function getCookieUser()
  {
    $cookied = Cookie::get($this->getCookieName());
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
   * @return IAuthProvider|null
   */
  public function getAuthProvider()
  {
    return $this->_authProvider;
  }

  public function forgottenPassword($username, array $options = null)
  {
    return $this->_authProvider->forgottenPassword($username, $options);
  }
}
