<?php

namespace Cubex\Tests\Http;

use Cubex\Routing\LazyHandler;
use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Http\Response;
use Packaged\Routing\Handler\FuncHandler;
use PHPUnit\Framework\TestCase;
use stdClass;

class LazyHandlerTest extends TestCase
{
  /**
   * @throws \Exception
   */
  public function testLazyHandler()
  {
    $ctx = new Context(Request::create('/'));
    $handler = new LazyHandler(
      function () {
        return new FuncHandler(function () { return Response::create('Lazy Func'); });
      }
    );
    $response = $handler->handle($ctx);
    self::assertStringContainsString('Lazy Func', $response->getContent());

    $handler = new LazyHandler(function () { return Response::create('Lazy Response'); });
    $response = $handler->handle($ctx);
    self::assertStringContainsString('Lazy Response', $response->getContent());

    $handler = new LazyHandler(function () { return new stdClass(); });
    $this->expectExceptionCode(500);
    $this->expectExceptionMessage("invalid lazy handler response object");
    $handler->handle($ctx);
  }
}
