<?php
namespace CubexTest\Auth;

use Cubex\Auth\AuthedUser;

class AuthedUserTest extends \PHPUnit_Framework_TestCase
{
  public function testBasics()
  {
    $user = new AuthedUser('brooke', 1, ['surname' => 'bryan']);

    $this->assertEquals(['surname' => 'bryan'], $user->getProperties());

    $this->assertEquals('brooke', $user->getUsername());
    $this->assertEquals(1, $user->getUserId());

    $this->assertEquals('bryan', $user->getProperty('surname', 'n'));
    $this->assertEquals('missing', $user->getProperty('nada', 'missing'));

    $user->setProperty('nada', 'found');
    $this->assertEquals('found', $user->getProperty('nada', 'missing'));
  }

  public function testSerialize()
  {
    $user = new AuthedUser(
      'Brooke',
      23,
      ['surname' => 'Bryan']
    );
    $string = $user->serialize();
    $userCompare = AuthedUser::fromString($string);
    $this->assertEquals($user->getUsername(), $userCompare->getUsername());
    $this->assertEquals($user->getUserId(), $userCompare->getUserId());
    $this->assertEquals(
      $user->getProperty('surname', ''),
      $userCompare->getProperty('surname', '')
    );
  }
}
