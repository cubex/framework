<?php

class IPAuthProviderTest extends PHPUnit_Framework_TestCase
{
  public function testLogout()
  {
    $auth = new \Cubex\Auth\Providers\IPAuthProvider();
    $this->assertFalse($auth->logout());
  }

  /**
   * @param $ip
   * @param $username
   * @param $userid
   * @param $display
   *
   * @throws Exception
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

    $cnf                 = [];
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
    $cnf['tester']       = [
      'username' => 'pet',
      'userid'   => 2,
      'display'  => 'Dog'
    ];
    $cnf['192.168.0.20'] = ['alias' => 'tester'];

    $configProvider = new \Packaged\Config\Provider\Test\TestConfigProvider();
    $authSection    = new \Packaged\Config\Provider\ConfigSection(
      'ipauth', $cnf
    );
    $configProvider->addSection($authSection);

    $cubex = new \Cubex\Cubex();
    $cubex->configure($configProvider);
    $request = new \Cubex\Http\Request();

    $request->server->set('REMOTE_ADDR', $ip);
    if($corruptRequest)
    {
      $cubex->instance('request', 'invalid');
    }
    else
    {
      $cubex->instance('request', $request);
    }
    $auth = new \Cubex\Auth\Providers\IPAuthProvider();
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
