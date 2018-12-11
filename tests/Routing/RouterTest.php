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
    $handler = new FuncHandler(function () { return Response::create('OK'); });
    $router->handle('/route', $handler);
    $this->assertSame($handler, $router->getHandler(Request::create('/route')));
    $this->assertNull($router->getHandler(Request::create('/not-found')));

    $router->handleFunc('/func', function () { return Response::create('OK'); });
    $handler = $router->getHandler(Request::create('/func'));
    $result = $handler->handle(new Context());
    $this->assertEquals('OK', $result->getContent());
  }
}
