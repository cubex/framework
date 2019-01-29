<?php

namespace Cubex\Tests\Routing;

use Cubex\Context\Context;
use Cubex\Http\FuncHandler;
use Cubex\Routing\RequestConstraint;
use Cubex\Routing\Route;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
  public function testRoute()
  {
    $route = Route::i();
    $handler = new FuncHandler(function () { });
    $route->setHandler($handler);
    $this->assertSame($handler, $route->getHandler());

    $ctx = new Context(Request::create('/route'));
    $this->assertTrue($route->match($ctx));
    $route->add(RequestConstraint::i()->path('/route'));
    $this->assertTrue($route->match($ctx));
    $route->add(RequestConstraint::i()->path('/'));
    $this->assertTrue($route->match($ctx));
    $route->add(RequestConstraint::i()->port('8484'));
    $this->assertFalse($route->match($ctx));
  }

  public function testRouteExtra()
  {
    $route = new Route();
    $handler = new FuncHandler(function () { });
    $route->setHandler($handler);

    $ctx = new Context(Request::create('/route_extra'));
    $route->add(RequestConstraint::i()->path('/route'));
    $this->assertFalse($route->match($ctx));
  }
}
