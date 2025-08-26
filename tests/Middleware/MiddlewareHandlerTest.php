<?php

namespace Cubex\Tests\Middleware;

use Cubex\Middleware\MiddlewareHandler;
use Cubex\Tests\Supporting\Middleware\MiddlewareHandlerTester;
use Cubex\Tests\Supporting\Middleware\SecondTestMiddleware;
use Cubex\Tests\Supporting\Middleware\TestFailedMiddleware;
use Cubex\Tests\Supporting\Middleware\TestMiddleware;
use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Routing\Handler\FuncHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MiddlewareHandlerTest extends TestCase
{

  public function testSuccess()
  {
    $handler = new MiddlewareHandler(new FuncHandler(fn ($c) => new RedirectResponse('/success')));
    $handler->add(new TestMiddleware());

    $resp = $handler->handle(new Context(Request::create('/testing')));
    self::assertInstanceOf(RedirectResponse::class, $resp);
    self::assertEquals('/success', $resp->getTargetUrl());
  }

  public function testFailed()
  {
    $handler = new MiddlewareHandler(new FuncHandler(fn ($c) => new RedirectResponse('/success')));
    $handler->add(new TestFailedMiddleware());

    $resp = $handler->handle(new Context(Request::create('/testing')));
    self::assertInstanceOf(RedirectResponse::class, $resp);
    self::assertEquals('/failed', $resp->getTargetUrl());
  }

  public function testPrepend()
  {
    $handler = new MiddlewareHandler(new FuncHandler(fn ($c) => new RedirectResponse('/success')));
    $handler->add(new TestMiddleware());
    $tester = MiddlewareHandlerTester::with($handler);
    self::assertCount(1, $tester->getMiddlewares());
    self::assertInstanceOf(TestMiddleware::class, $tester->getMiddlewares()[0]);

    $handler->prepend(new SecondTestMiddleware());
    self::assertCount(2, $tester->getMiddlewares());
    self::assertInstanceOf(SecondTestMiddleware::class, $tester->getMiddlewares()[0]);
    self::assertInstanceOf(TestMiddleware::class, $tester->getMiddlewares()[1]);
  }

  public function testAdd()
  {
    $handler = new MiddlewareHandler(new FuncHandler(fn ($c) => new RedirectResponse('/success')));
    $handler->add(new TestMiddleware());
    $tester = MiddlewareHandlerTester::with($handler);
    self::assertCount(1, $tester->getMiddlewares());
    self::assertInstanceOf(TestMiddleware::class, $tester->getMiddlewares()[0]);

    $handler->append(new SecondTestMiddleware());
    self::assertCount(2, $tester->getMiddlewares());
    self::assertInstanceOf(TestMiddleware::class, $tester->getMiddlewares()[0]);
    self::assertInstanceOf(SecondTestMiddleware::class, $tester->getMiddlewares()[1]);
  }

  public function testAppend()
  {
    $this->testAdd();
  }

  public function testReplace()
  {
    $handler = new MiddlewareHandler(new FuncHandler(fn ($c) => new RedirectResponse('/success')));
    $handler->add(new TestMiddleware());
    $tester = MiddlewareHandlerTester::with($handler);
    self::assertCount(1, $tester->getMiddlewares());
    self::assertInstanceOf(TestMiddleware::class, $tester->getMiddlewares()[0]);

    $handler->replace(TestMiddleware::class, new SecondTestMiddleware());
    self::assertCount(1, $tester->getMiddlewares());
    self::assertInstanceOf(SecondTestMiddleware::class, $tester->getMiddlewares()[0]);
  }

  public function testRemove()
  {
    $handler = new MiddlewareHandler(new FuncHandler(fn ($c) => new RedirectResponse('/success')));
    $handler->add(new TestMiddleware());
    $tester = MiddlewareHandlerTester::with($handler);
    self::assertCount(1, $tester->getMiddlewares());
    self::assertInstanceOf(TestMiddleware::class, $tester->getMiddlewares()[0]);

    $handler->remove(TestMiddleware::class);
    self::assertCount(0, $tester->getMiddlewares());
  }

}
