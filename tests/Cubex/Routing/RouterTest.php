<?php

class RouterTest extends CubexTestCase
{
  public function testCreateRoute()
  {
    $route  = \Cubex\Routing\Route::create("my route");
    $router = new \Cubex\Routing\Router();
    $this->assertSame($route, $router->createRoute($route));
    $this->assertInstanceOf('\Cubex\Routing\Route', $router->createRoute("ab"));
  }

  /**
   * @dataProvider patternMatches
   */
  public function testMatchPattern($url, $pattern, $expect)
  {
    $router = new \Cubex\Routing\Router();
    $this->assertEquals($expect, $router->matchPattern($url, $pattern));
  }

  public function patternMatches()
  {
    return [
      ["hello/world", "hello", true],
      ["hello/world", "hello/world", true],
      ["hello/world", "world", false],
      ["hello/world", null, false],
    ];
  }

  /**
   * @dataProvider stripPatterns
   */
  public function testStripUrlWithPattern($url, $pattern, $expect)
  {
    $router = new \Cubex\Routing\Router();
    $this->assertEquals($expect, $router->stripUrlWithPattern($url, $pattern));
  }

  public function stripPatterns()
  {
    return [
      ["hello/world", 'hello/world', ''],
      ["hello/world", 'hello', '/world'],
    ];
  }

  public function testProcessNoSubjectException()
  {
    $this->setExpectedException(
      "RuntimeException",
      "No routable subject has been defined"
    );
    $router = new \Cubex\Routing\Router();
    $router->process("/hello");
  }

  /**
   * @dataProvider routeAttempts
   */
  public function testProcess($url, $routes, $expect, $expectException = false)
  {
    if($expectException)
    {
      $this->setExpectedException(
        "Exception",
        "Unable to locate a suitable route"
      );
    }
    $router = new \Cubex\Routing\Router();
    $router->setCubex($this->newCubexInstace());
    $subject = $this->getMock('\Cubex\Routing\IRoutable', ['getRoutes']);
    $subject->expects($this->any())->method("getRoutes")
      ->will($this->returnValue($routes));
    $router->setSubject($subject);
    $route = $router->process($url);

    if(!$expectException)
    {
      $this->assertInstanceOf('\Cubex\Routing\Route', $route);
      $this->assertEquals($expect, $route->getValue());
    }
  }

  public function routeAttempts()
  {
    return [
      ["/hello", null, null, true],
      ["/hello", [], null, true],
      ["/hello", ['hello' => 'pass'], 'pass'],
      ["/hello/world", ['hello' => ['world' => 'pass']], 'pass'],
      ["/hello/world", ['hello' => ['world' => null]], null],
      [
        "/hello/world",
        ['hello' => ['world' => ['test', 'array']]],
        ['test', 'array']
      ],
      [
        "/hello/world/test",
        ['hello' => ['world' => ['test' => 'pass']]],
        'pass'
      ],
    ];
  }

  /**
   * @dataProvider simpleRouteProvider
   */
  public function testSimpleRoutes($route, $expect)
  {
    $this->assertEquals(
      $expect,
      \Cubex\Routing\Router::convertSimpleRoute($route)
    );
  }

  public function simpleRouteProvider()
  {
    return [
      ['{var@alpha}', '(?P<var>\w+)'],
      ['{var@all}', '(?P<var>.+)'],
      ['{var@num}', '(?P<var>\d+)'],
      ['{var}', '(?P<var>[^\/]+)'],
      [':var@alpha', '(?P<var>\w+)'],
      [':var@all', '(?P<var>.+)'],
      [':var@num', '(?P<var>\d+)'],
      [':var', '(?P<var>[^\/]+)'],
    ];
  }

  public function testRouteData()
  {
    $router = new \Cubex\Routing\Router();
    $router->setCubex($this->newCubexInstace());
    $subject = $this->getMock('\Cubex\Routing\IRoutable', ['getRoutes']);
    $subject->expects($this->any())->method("getRoutes")
      ->will($this->returnValue([':test/:number@num' => 'test']));
    $router->setSubject($subject);
    $route  = $router->process('hello/23');
    $expect = ["test" => "hello", "number" => 23];
    $this->assertEquals($expect, $route->getRouteData());
  }
}
