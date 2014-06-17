<?php

class CookieTest extends PHPUnit_Framework_TestCase
{
  public function testMake()
  {
    $cookie = \Cubex\Facade\Cookie::make('tester', 'values');
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Cookie',
      $cookie
    );
    $this->assertEquals('tester', $cookie->getName());
    $this->assertEquals('values', $cookie->getValue());
  }

  public function testGet()
  {
    $cubex   = new \Cubex\Cubex();
    $request = new \Cubex\Http\Request([], [], [], ['ckey' => 'cookie']);
    $cubex->instance('request', $request);
    $app = \Cubex\Facade\Cookie::getFacadeApplication();
    \Cubex\Facade\Cookie::setFacadeApplication($cubex);
    $this->assertEquals('cookie', \Cubex\Facade\Cookie::get('ckey'));
    $this->assertEquals('default', \Cubex\Facade\Cookie::get('cs', 'default'));
    \Cubex\Facade\Cookie::setFacadeApplication($app);
  }

  public function testQueue()
  {
    $cookie = new \Symfony\Component\HttpFoundation\Cookie('test', 'val');
    \Cubex\Facade\Cookie::queue($cookie);
    $this->assertTrue(\Cubex\Facade\Cookie::getJar()->hasQueued('test'));
  }

  public function testDelete()
  {
    \Cubex\Facade\Cookie::delete('deletetest');
    $this->assertTrue(\Cubex\Facade\Cookie::getJar()->hasQueued('deletetest'));
  }

  public function testForget()
  {
    $cookie = \Cubex\Facade\Cookie::forget('testforget');
    $this->assertTrue($cookie->getExpiresTime() < time());
    $this->assertEquals('testforget', $cookie->getName());
  }
}
