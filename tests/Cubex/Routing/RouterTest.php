<?php
namespace CubexTest\Cubex\Routing;

use Cubex\Routing\Route;
use Cubex\Routing\Router;
use CubexTest\InternalCubexTestCase;

class RouterTestInternal extends InternalCubexTestCase
{
  public function testCreateRoute()
  {
    $route = Route::create("my route");
    $router = new Router();
    $this->assertSame($route, $router->createRoute($route));
    $this->assertInstanceOf(
      '\Cubex\Routing\Route',
      $router->createRoute(
        "ab",
        ''
      )
    );
  }

  /**
   * @dataProvider patternMatches
   */
  public function testMatchPattern($url, $pattern, $expect)
  {
    $router = new Router();
    $this->assertEquals($expect, $router->matchPattern($url, $pattern));
  }

  public function patternMatches()
  {
    return [
      ["hello/world", "hello", 'hello/'],
      ["hello/world", "hello/world", 'hello/world'],
      ["hello/world", "hello(.*)", 'hello/'],
      ["hello/world", "world", false],
      ["hello/world", null, false],
      ["helloworld", "hello", false],
    ];
  }

  public function testProcessNoSubjectException()
  {
    $this->setExpectedException(
      "RuntimeException",
      "No routable subject has been defined"
    );
    $router = new Router();
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
    $router = new Router();
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
        null,
        true
      ],
      [
        "/hello/world/test",
        ['hello' => ['world' => ['test' => 'pass']]],
        'pass'
      ],
      [
        '/hello/world/this/is/long',
        ['hello' => ['world' => ['.*' => 'pass']]],
        'pass'
      ],
      [
        '/hello/world/this/is/long',
        ['hello' => ['world' => 'pass']],
        'pass'
      ]
    ];
  }

  /**
   * @dataProvider simpleRouteProvider
   */
  public function testSimpleRoutes($route, $expect)
  {
    $this->assertEquals(
      $expect,
      Router::convertSimpleRoute($route)
    );
  }

  public function simpleRouteProvider()
  {
    return [
      ['{var@alpha}', '(?P<var>[a-zA-Z]+)'],
      ['{var@all}', '(?P<var>.+?)'],
      ['{var@num}', '(?P<var>\d+)'],
      ['{var@alphanum}', '(?P<var>\w+)'],
      ['{var@alnum}', '(?P<var>\w+)'],
      ['{var}', '(?P<var>.+)'],
      [':var@alpha', '(?P<var>[a-zA-Z]+)'],
      [':var@all', '(?P<var>.+?)'],
      [':var@num', '(?P<var>\d+)'],
      [':var@alphanum', '(?P<var>\w+)'],
      [':var@alnum', '(?P<var>\w+)'],
      [':var', '(?P<var>.+)'],
    ];
  }

  public function testRouteData()
  {
    $router = new Router();
    $router->setCubex($this->newCubexInstace());
    $subject = $this->getMock('\Cubex\Routing\IRoutable', ['getRoutes']);
    $subject->expects($this->any())->method("getRoutes")
      ->will($this->returnValue([':test/:number@num' => 'test']));
    $router->setSubject($subject);
    $route = $router->process('hello/23');
    $expect = ["test" => "hello", "number" => 23];
    $this->assertEquals($expect, $route->getRouteData());
  }
}
