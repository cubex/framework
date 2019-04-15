<?php

namespace Cubex\Tests\Routing;

use Cubex\Context\Context;
use Cubex\Http\FuncHandler;
use Cubex\Routing\Router;
use Packaged\Http\Request;
use Packaged\Http\Response;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
  public function testRouter()
  {
    $router = Router::i();
    $this->assertNull($router->getHandler(new Context(Request::create('/not-found)'))));
    $router->setDefaultHandler(new FuncHandler(function () { return Response::create('Default'); }));
    $handler = $router->getHandler(new Context(Request::create('/not-found')));
    $this->assertEquals('Default', $handler->handle(new Context())->getContent());

    $handler = new FuncHandler(function () { return Response::create('OK'); });
    $router->onPath('/route', $handler);
    $this->assertSame($handler, $router->getHandler(new Context(Request::create('/route'))));

    $router->onPathFunc('/func', function () { return Response::create('OK'); });
    $handler = $router->getHandler(new Context(Request::create('/func')));
    $result = $handler->handle(new Context());
    $this->assertEquals('OK', $result->getContent());
  }
}
