<?php

namespace Cubex\Tests\Http;

use Packaged\Context\Context;
use Cubex\Http\FuncHandler;
use Cubex\Http\LazyHandler;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

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
    $this->assertContains('Lazy Func', $response->getContent());

    $handler = new LazyHandler(function () { return Response::create('Lazy Response'); });
    $response = $handler->handle($ctx);
    $this->assertContains('Lazy Response', $response->getContent());

    $handler = new LazyHandler(function () { return new stdClass(); });
    $this->expectExceptionCode(500);
    $this->expectExceptionMessage("invalid lazy handler response object");
    $handler->handle($ctx);
  }
}
