<?php

namespace Cubex\Tests\Routing;

use Cubex\Routing\Router;
use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Http\Response;
use Packaged\Http\Responses\RedirectResponse;
use Packaged\Routing\Handler\FuncHandler;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
  public function testRouter()
  {
    $router = Router::i();
    self::assertNull($router->getHandler(new Context(Request::create('/not-found)'))));
    $router->setDefaultHandler(new FuncHandler(function () { return Response::create('Default'); }));
    $handler = $router->getHandler(new Context(Request::create('/not-found')));
    self::assertEquals('Default', $handler->handle(new Context())->getContent());

    $handler = new FuncHandler(function () { return Response::create('OK'); });
    $router->onPath('/route', $handler);
    self::assertSame($handler, $router->getHandler(new Context(Request::create('/route'))));

    $router->onPathFunc('/func', function () { return Response::create('OK'); });
    $handler = $router->getHandler(new Context(Request::create('/func')));
    $result = $handler->handle(new Context());
    self::assertEquals('OK', $result->getContent());
  }

  public function testRedirect()
  {
    $router = Router::i();
    $handleStr = "@301!/redirected";
    $router->onPath('/redirect', $handleStr);

    $ctx = new Context(Request::create('/redirect'));
    $handler = $router->getHandler($ctx);
    self::assertEquals($handleStr, (string)$handler);

    $response = $router->handle($ctx);
    self::assertInstanceOf(RedirectResponse::class, $response);
    self::assertEquals(301, $response->getStatusCode());
    self::assertEquals('/redirected', $response->getTargetUrl());
  }
}
