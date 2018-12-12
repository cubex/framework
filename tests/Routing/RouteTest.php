<?php

namespace Cubex\Tests\Routing;

use Cubex\Context\Context;
use Cubex\Http\FuncHandler;
use Cubex\Routing\Constraint;
use Cubex\Routing\Route;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
  public function testRoute()
  {
    $route = new Route();
    $handler = new FuncHandler(function () { });
    $route->setHandler($handler);
    $this->assertSame($handler, $route->getHandler());

    $ctx = new Context(Request::create('/route'));
    $this->assertTrue($route->match($ctx));
    $route->add(Constraint::path('/route'));
    $this->assertTrue($route->match($ctx));
    $route->add(Constraint::port('8484'));
    $this->assertFalse($route->match($ctx));
  }
}
