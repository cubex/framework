<?php

class AuthServiceTest extends PHPUnit_Framework_TestCase
{
  /**
   * @return \Cubex\ServiceManager\Services\AuthService
   */
  public function getAuthService()
  {
    $cubex = new \Cubex\Cubex();
    $cubex->configure(new \Packaged\Config\Provider\Test\TestConfigProvider());
    $cubex->processConfiguration($cubex->getConfiguration());
    $cubex->instance('request', \Cubex\Http\Request::createFromGlobals());
    $cubex->instance('\Cubex\Auth\IAuthProvider', new TestAuthProvider());
    $sm = new \Cubex\ServiceManager\ServiceManager();
    $sm->setCubex($cubex);
    $sm->boot();
    return $cubex['auth'];
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
    $app  = \Cubex\Facade\Cookie::getFacadeApplication();
    $auth = $this->getAuthService();
    \Cubex\Facade\Cookie::setFacadeApplication($auth->getCubex());
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
    $cookies = \Cubex\Facade\Cookie::getJar();
    /**
     * @var $cookies Illuminate\Cookie\CookieJar
     */
    $this->assertTrue($cookies->hasQueued('cubex_login'));
    \Cubex\Facade\Cookie::setFacadeApplication($app);
  }

  public function testGetAuthedUser()
  {
    $auth     = $this->getAuthService();
    $provider = $auth->getAuthProvider();
    $usr      = new \Cubex\Auth\AuthedUser(
      'brooke', 56, ['surname' => 'Bryan']
    );

    if($provider instanceof TestAuthProvider)
    {
      $provider->setRetrieve($usr);
      $this->assertSame($usr, $auth->getAuthedUser());
      $auth->logout();
    }

    $request = \Cubex\Facade\Cookie::getFacadeApplication()->make('request');
    if($request instanceof \Cubex\Http\Request)
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
}

class TestAuthProvider implements \Cubex\Auth\IAuthProvider
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
      return new \Cubex\Auth\AuthedUser('user', 1);
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
   * @throws Exception
   */
  public function retrieveUser()
  {
    if($this->_retrieve === null)
    {
      throw new Exception('Unable to auth user');
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
    throw new Exception('User not found');
  }
}
