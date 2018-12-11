<?php

namespace Cubex\Tests\Controller;

use Cubex\Context\Context;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Controller\TestController;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
  public function testController()
  {
    $cubex = new Cubex(__DIR__, null, false);

    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));

    $controller = new TestController();
    $controller->setContext($cubex->getContext());
    $this->assertSame($request, $controller->getRequest());

    $this->assertTrue($controller->canProcess());

    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('GET ROUTE', $response->getContent());

    $request = Request::create("/route", 'POST');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('POST ROUTE', $response->getContent());

    $request = Request::create("/route");
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('AJAX GET ROUTE', $response->getContent());

    $request = Request::create("/ui");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Testing UI Route', $response->getContent());

    $request = Request::create("/buffered");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('BUFFER', $response->getContent());

    $request = Request::create("/response");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Fixed Response', $response->getContent());

    $request = Request::create("/missing");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage('unable to handle your request');
    $controller->handle($cubex->getContext());
  }
}
