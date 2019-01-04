<?php

namespace Cubex\Tests\Controller;

use Cubex\Context\Context;
use Cubex\Controller\Controller;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Controller\TestController;
use Packaged\Http\Request;
use Packaged\Http\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
  }

  public function testGetRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('GET ROUTE', $response->getContent());
  }

  public function testPostRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/route", 'POST');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('POST ROUTE', $response->getContent());
  }

  public function testAjaxRouting()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/route");
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('AJAX GET ROUTE', $response->getContent());
  }

  public function testUiResponse()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/ui");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Testing UI Route', $response->getContent());
  }

  public function testBufferedResponse()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/buffered");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('BUFFER', $response->getContent());
  }

  public function testResponseResponse()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/response");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Fixed Response', $response->getContent());
  }

  public function testSubClass()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/sub/route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Default', $response->getContent());
  }

  public function testSubClassFullRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/sub/router");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Router', $response->getContent());
  }

  public function testInvalidSubClass()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/badsub");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_INVALID_ROUTE_RESPONSE);
    $controller->handle($cubex->getContext());
  }

  public function testResponseClass()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/default-response");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Access Denied', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  public function testInvalidRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/missing");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }

  public function testNoRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/not-found");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }

  public function testExceptionalRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/exception");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage("Broken");
    $controller->handle($cubex->getContext());
  }

  public function testAuthResponse()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestController();
    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals(200, $response->getStatusCode());

    $authFailResponse = RedirectResponse::create('/login', 302);
    $controller->setAuthResponse($authFailResponse);
    $response = $controller->handle($cubex->getContext());
    $this->assertInstanceOf(RedirectResponse::class, $response);
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertSame($authFailResponse, $response);

    $controller->setAuthResponse(false);
    $this->expectExceptionMessage(Controller::ERROR_ACCESS_DENIED);
    $controller->handle($cubex->getContext());
  }
}
