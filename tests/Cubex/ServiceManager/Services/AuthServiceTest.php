<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Auth\AuthedUser;
use Cubex\Auth\IAuthProvider;
use Cubex\Cubex;
use Cubex\Facade\Auth;
use Cubex\Facade\Cookie;
use Cubex\Http\Request;
use Cubex\ServiceManager\ServiceManager;
use Illuminate\Cookie\CookieJar;
use Packaged\Config\Provider\Test\TestConfigProvider;

class AuthServiceTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @return \Cubex\ServiceManager\Services\AuthService
   */
  public function getAuthService()
  {
    $cubex = new Cubex();
    $cubex->configure(new TestConfigProvider());
    $cubex->processConfiguration($cubex->getConfiguration());
    $cubex->instance('request', Request::createFromGlobals());
    $cubex->instance('\Cubex\Auth\IAuthProvider', new TestAuthProvider());
    $sm = new ServiceManager();
    $sm->setCubex($cubex);
    $sm->boot();
    return $cubex['auth'];
  }

  public function testAuthFacade()
  {
    $provider = new TestAuthProvider();
    $authy = new AuthedUser('brooke', 1);
    $provider->setRetrieve($authy);

    $cubex = new Cubex();
    $cubex->configure(new TestConfigProvider());
    $cubex->processConfiguration($cubex->getConfiguration());
    $cubex->instance('request', Request::createFromGlobals());
    $cubex->instance('\Cubex\Auth\IAuthProvider', $provider);
    $sm = new ServiceManager();
    $sm->setCubex($cubex);
    $sm->boot();
    Auth::setFacadeApplication($cubex);

    $username = 'valid';
    $this->assertTrue(Auth::forgottenPassword($username));
    $authUser = Auth::login($username, 'password');
    $this->assertEquals("brooke", $authUser->getUsername());

    $autho = Auth::getAuthedUser();
    $this->assertEquals("brooke", $autho->getUsername());
    Auth::updateAuthedUser($autho);
    $this->assertTrue(Auth::isLoggedIn());
    $this->assertTrue(Auth::logout());
    $this->assertFalse(Auth::isLoggedIn());
  }

  public function testInvalidLoginException()
  {
    $this->setExpectedException('\RuntimeException', "Unable to login 'user'");
    $auth = $this->getAuthService();
    $auth->login('user', 'password');
  }

  public function testForgottenPasswordException()
  {
    $this->setExpectedException('\Exception', "User not found");
    $auth = $this->getAuthService();
    $auth->forgottenPassword('user');
  }

  public function testAuthService()
  {
    $app = Cookie::getFacadeApplication();
    $auth = $this->getAuthService();
    Cookie::setFacadeApplication($auth->getCubex());
    $this->assertInstanceOf(
      '\Cubex\ServiceManager\Services\AuthService',
      $auth
    );
    /**
     * @var $auth \Cubex\ServiceManager\Services\AuthService
     */
    $this->assertFalse($auth->logout());
    $this->assertFalse($auth->isLoggedIn());

    $this->assertInstanceOf(
      '\Cubex\Auth\IAuthedUser',
      $auth->login('valid', 'password')
    );

    $this->assertTrue($auth->isLoggedIn());

    $this->assertEquals('cubex_login', $auth->getCookieName());
    $cookies = Cookie::getJar();
    /**
     * @var $cookies CookieJar
     */
    $this->assertTrue($cookies->hasQueued('cubex_login'));
    Cookie::setFacadeApplication($app);
  }

  public function testGetAuthedUser()
  {
    $auth = $this->getAuthService();
    $provider = $auth->getAuthProvider();
    $usr = new AuthedUser(
      'brooke', 56, ['surname' => 'Bryan']
    );

    if($provider instanceof TestAuthProvider)
    {
      $provider->setRetrieve($usr);
      $this->assertSame($usr, $auth->getAuthedUser());
      $auth->logout();
    }

    $request = Cookie::getFacadeApplication()->make('request');
    if($request instanceof Request)
    {
      $request->cookies->set('cubex_login', 'InvalidCookie');
      $this->assertFalse($auth->isLoggedIn());
      $auth->logout();

      $request->cookies->set('cubex_login', $usr->serialize());
      $this->assertEquals('brooke', $auth->getAuthedUser()->getUsername());
      $auth->logout();

      $request->cookies->remove('cubex_login');
    }
  }

  public function testUpdateAuthedUser()
  {
    $auth = $this->getAuthService();
    $usr = new AuthedUser(
      'brooke', 56, ['surname' => 'Bryan', 'test' => 'one']
    );
    //$request = Cookie::getFacadeApplication()->make('request');
    /**
     * @var $request \Cubex\Http\Request
     */

    $provider = new TestAuthProvider();
    $provider->setRetrieve($usr);
    $auth->setAuthProvider($provider);

    $auth->login('valid', '');
    $this->assertEquals('brooke', $auth->getCookieUser()->getUsername());
    $this->assertEquals('one', $auth->getCookieUser()->getProperty('test'));

    $usr->setProperty('test', 'three');
    $auth->updateAuthedUser($usr);
    $this->assertEquals('three', $auth->getCookieUser()->getProperty('test'));

    $auth->logout();
  }
}

class TestAuthProvider implements IAuthProvider
{
  protected $_retrieve;

  /**
   * @param       $username
   * @param       $password
   * @param array $options
   *
   * @return \Cubex\Auth\IAuthedUser|null
   */
  public function login($username, $password, array $options = null)
  {
    if($username == 'valid')
    {
      if(isset($this->_retrieve))
      {
        return $this->_retrieve;
      }
      return new AuthedUser('user', 1);
    }
    return null;
  }

  /**
   * @return bool
   */
  public function logout()
  {
    if($this->_retrieve !== null)
    {
      $this->_retrieve = null;
      return true;
    }
    return false;
  }

  /**
   * Retrieve the logged in user dynamically, e.g. off sessions or ips
   *
   * @return \Cubex\Auth\IAuthedUser
   *
   * @throws \Exception
   */
  public function retrieveUser()
  {
    if($this->_retrieve === null)
    {
      throw new \Exception('Unable to auth user');
    }
    return $this->_retrieve;
  }

  public function setRetrieve($user)
  {
    $this->_retrieve = $user;
    return $this;
  }

  public function forgottenPassword($username, array $options = null)
  {
    if($username == 'valid')
    {
      return true;
    }
    throw new \Exception('User not found');
  }
}
