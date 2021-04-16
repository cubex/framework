<?php

namespace Cubex\Tests\Routing;

use Packaged\Context\Context;
use Packaged\Http\Requests\HttpRequest;
use Packaged\Routing\Handler\FuncHandler;
use Packaged\Routing\RequestCondition;
use Packaged\Routing\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
  public function testRoutePart()
  {
    [$route, $ctx] = $this->_getRoute();

    $route->add(RequestCondition::i()->path('route'));
    self::assertTrue($route->match($ctx));
  }

  public function testRouteRootRoute()
  {
    [$route, $ctx] = $this->_getRoute();

    $route->add(RequestCondition::i()->path('/route'));
    self::assertTrue($route->match($ctx));
  }

  public function testRouteRoot()
  {
    [$route, $ctx] = $this->_getRoute();

    $route->add(RequestCondition::i()->path('/'));
    self::assertTrue($route->match($ctx));
  }

  public function testRoutePort()
  {
    [$route, $ctx] = $this->_getRoute();
    $route->add(RequestCondition::i()->port('8484'));
    self::assertFalse($route->match($ctx));
  }

  public function testRouteExtra()
  {
    $route = new Route();
    $handler = new FuncHandler(function () { });
    $route->setHandler($handler);

    $ctx = new Context(HttpRequest::create('/route_extra'));
    $route->add(RequestCondition::i()->path('/route'));
    self::assertFalse($route->match($ctx));
  }

  /**
   * @return array
   */
  private function _getRoute(): array
  {
    $route = Route::i();
    $handler = new FuncHandler(function () { });
    $route->setHandler($handler);
    self::assertSame($handler, $route->getHandler());

    $ctx = new Context(HttpRequest::create('/route'));
    self::assertTrue($route->match($ctx));
    return [$route, $ctx];
  }
}
