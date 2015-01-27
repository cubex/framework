<?php
namespace CubexTest\Cubex\Facade;

use Cubex\Cubex;
use Cubex\Facade\Cookie;
use Cubex\Http\Request;

class CookieTest extends \PHPUnit_Framework_TestCase
{
  public function testMake()
  {
    $cookie = Cookie::make('tester', 'values');
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Cookie',
      $cookie
    );
    $this->assertEquals('tester', $cookie->getName());
    $this->assertEquals('values', $cookie->getValue());
  }

  public function testGet()
  {
    $cubex = new Cubex();
    $request = new Request([], [], [], ['ckey' => 'cookie']);
    $cubex->instance('request', $request);
    $app = Cookie::getFacadeApplication();
    Cookie::setFacadeApplication($cubex);
    $this->assertEquals('cookie', Cookie::get('ckey'));
    $this->assertEquals('default', Cookie::get('cs', 'default'));
    Cookie::setFacadeApplication($app);
  }

  public function testQueue()
  {
    $cookie = new \Symfony\Component\HttpFoundation\Cookie('test', 'val');
    Cookie::queue($cookie);
    $this->assertTrue(Cookie::getJar()->hasQueued('test'));
  }

  public function testDelete()
  {
    Cookie::delete('deletetest');
    $this->assertTrue(Cookie::getJar()->hasQueued('deletetest'));
  }

  public function testForget()
  {
    $cookie = Cookie::forget('testforget');
    $this->assertTrue($cookie->getExpiresTime() < time());
    $this->assertEquals('testforget', $cookie->getName());
  }
}
