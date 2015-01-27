<?php
namespace CubexTest\Auth\Providers;

use Cubex\Auth\Providers\IPAuthProvider;
use Cubex\Cubex;
use Cubex\Http\Request;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Config\Provider\Test\TestConfigProvider;

class IPAuthProviderTest extends \PHPUnit_Framework_TestCase
{
  public function testLogout()
  {
    $auth = new IPAuthProvider();
    $this->assertFalse($auth->logout());
  }

  public function testForgottenPassword()
  {
    $auth = new IPAuthProvider();
    $this->setExpectedException(
      '\Exception',
      'Forgotten Password is not available'
    );
    $auth->forgottenPassword('user');
  }

  /**
   * @param $ip
   * @param $username
   * @param $userid
   * @param $display
   * @param $exception
   * @param $corruptRequest
   *
   * @throws \Exception
   *
   * @dataProvider ipOptions
   */
  public function testRetrieve(
    $ip, $username, $userid, $display, $exception = null,
    $corruptRequest = false
  )
  {
    if($exception !== null)
    {
      $this->setExpectedException('\Exception', $exception);
    }

    $cnf = [];
    $cnf['192.168.0.10'] = [
      'username' => 'bob',
      'userid'   => 3,
      'display'  => 'Bobby'
    ];
    $cnf['192.168.0.11'] = [
      'userid'  => 3,
      'display' => 'Bobby'
    ];
    $cnf['192.168.0.12'] = [
      'username' => 'bob',
      'display'  => 'Bobby'
    ];
    $cnf['tester'] = [
      'username' => 'pet',
      'userid'   => 2,
      'display'  => 'Dog'
    ];
    $cnf['192.168.0.20'] = ['alias' => 'tester'];

    $configProvider = new TestConfigProvider();
    $authSection = new ConfigSection('ipauth', $cnf);
    $configProvider->addSection($authSection);

    $cubex = new Cubex();
    $cubex->configure($configProvider);
    $request = new Request();

    $request->server->set('REMOTE_ADDR', $ip);
    if($corruptRequest)
    {
      $cubex->instance('request', 'invalid');
    }
    else
    {
      $cubex->instance('request', $request);
    }
    $auth = new IPAuthProvider();
    $auth->setCubex($cubex);
    $user = $auth->login('test', 'test');

    if($username === null)
    {
      $this->assertNull($user);
    }
    else
    {
      $this->assertEquals($username, $user->getUsername());
      $this->assertEquals($userid, $user->getUserId());
      $this->assertEquals($display, $user->getProperty('display'));
    }
  }

  public function ipOptions()
  {
    return [
      ['192.168.0.20', 'pet', 2, 'Dog'],
      ['192.168.0.10', 'bob', 3, 'Bobby'],
      ['192.168.0.11', null, null, null, 'Invalid IP Configuration'],
      [
        '192.168.0.11',
        null,
        null,
        null,
        "Unable to retrieve the users IP",
        true
      ],
      ['192.168.0.12', 'bob', 0, 'Bobby'],
      ['192.168.0.13', null, null, null, 'Unauthorized IP'],
    ];
  }
}
