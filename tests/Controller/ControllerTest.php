<?php

namespace Cubex\Tests\Controller;

use Cubex\Context\Context;
use Cubex\Controller\Controller;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Controller\TestArrayRouteController;
use Cubex\Tests\Supporting\Controller\TestController;
use Cubex\Tests\Supporting\Controller\TestIncompleteController;
use Packaged\Http\Request;
use Packaged\Http\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ControllerTest extends TestCase
{
  public function controllersProvider()
  {
    return [
      [new TestController()],
      [new TestArrayRouteController()],
    ];
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testController(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));

    $controller->setContext($cubex->getContext());
    $this->assertSame($request, $controller->request());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testGetRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('GET ROUTE', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testGetSafeHtml(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/safe-html");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('<b>Test</b>', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testPostRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/route", 'POST');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('POST ROUTE', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testAjaxRouting(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/route");
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('AJAX GET ROUTE', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testUiResponse(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/ui");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Testing UI Route', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testBufferedResponse(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/buffered");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('BUFFER', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testResponseResponse(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/response");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Fixed Response', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testSubClass(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/sub/route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Default', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testSubClassFullRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/sub/router");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Router', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testCallableRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/sub/call");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Remote', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testRouteData(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/subs/testing");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertEquals('testing', $controller->routeData()->get('dynamic'));
    $this->assertStringContainsString('Default', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testInvalidSubClass(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/badsub");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_INVALID_ROUTE_RESPONSE);
    $controller->handle($cubex->getContext());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testResponseClass(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/default-response");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('Access Denied', $response->getContent());
    $this->assertEquals(403, $response->getStatusCode());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testInvalidRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/missing");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testNoRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/not-found");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testExceptionalRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/exception");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage("Broken");
    $controller->handle($cubex->getContext());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testHandlerRoute(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create("/handler-route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertEquals($response->getContent(), 'handled route');
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param Controller|TestController|TestArrayRouteController $controller
   *
   * @throws \Throwable
   */
  public function testAuthResponse(Controller $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
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

  /**
   * @throws \Throwable
   */
  public function testIncompleteController()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestIncompleteController();
    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));
    $this->expectExceptionMessage(Controller::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }
}
