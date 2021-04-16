<?php

namespace Cubex\Tests\Controller;

use Cubex\Controller\AuthedController;
use Cubex\Controller\Controller;
use Cubex\Cubex;
use Cubex\Events\PreExecuteEvent;
use Cubex\Routing\RouteProcessor;
use Cubex\Tests\Supporting\Controller\SubTestController;
use Cubex\Tests\Supporting\Controller\TestArrayRouteController;
use Cubex\Tests\Supporting\Controller\TestController;
use Cubex\Tests\Supporting\Controller\TestIncompleteController;
use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Http\Requests\HttpRequest;
use Packaged\Http\Response;
use Packaged\Http\Responses\RedirectResponse;
use Packaged\Routing\ConditionHandler;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
  public function controllersProvider()
  {
    return [
      [new TestController()],
      [new TestArrayRouteController()],
    ];
  }

  protected function _prepareCubex(Cubex $cubex, Request $request)
  {
    $ctx = new Context($request);
    $cubex->share(Context::class, $ctx);
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testController(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/route");
    $this->_prepareCubex($cubex, $request);

    $controller->setContext($cubex->getContext());
    self::assertSame($request, $controller->request());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testGetRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/route");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('GET ROUTE', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testGetSafeHtml(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/safe-html");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('<b>Test</b>', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testGetRedirect(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/google");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertInstanceOf(RedirectResponse::class, $response);
    self::assertTrue($response->isRedirect('http://www.google.com'));
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testPostRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/route", 'POST');
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('POST ROUTE', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testAjaxRouting(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/route");
    $request->headers()->set('X-Requested-With', 'XMLHttpRequest');
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('AJAX GET ROUTE', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testUiResponse(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/ui");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('Testing UI Route', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testBufferedResponse(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/buffered");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('BUFFER', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testResponseResponse(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/response");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('Fixed Response', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testSubClass(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/sub/route");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('Default', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testSubClassFullRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/sub/router");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('Router', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testCallableRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/sub/call");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('Remote', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testRouteData(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/subs/testing");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertEquals('testing', $controller->routeData()->get('dynamic'));
    self::assertStringContainsString('Default', $response->getContent());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testInvalidSubClass(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/badsub");
    $this->_prepareCubex($cubex, $request);
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
  public function testResponseClass(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/default-response");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertStringContainsString('Access Denied', $response->getContent());
    self::assertEquals(403, $response->getStatusCode());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testInvalidRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/missing");
    $this->_prepareCubex($cubex, $request);
    $this->expectExceptionMessage(RouteProcessor::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }

  /**
   * @throws \Throwable
   */
  public function testNoRouteWithDefault()
  {
    $controller = new TestController();
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/not-found");
    $this->_prepareCubex($cubex, $request);
    $this->expectExceptionMessage(RouteProcessor::ERROR_NO_ROUTE);
    $controller->handle($cubex->getContext());
  }

  /**
   * @throws \Throwable
   */
  public function testNoRoute()
  {
    $controller = new TestArrayRouteController();
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/not-found");
    $this->_prepareCubex($cubex, $request);
    $this->expectExceptionMessage(ConditionHandler::ERROR_NO_HANDLER);
    $controller->handle($cubex->getContext());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param $controller
   *
   * @throws \Throwable
   */
  public function testExceptionalRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/exception");
    $this->_prepareCubex($cubex, $request);
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
  public function testHandlerRoute(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/handler-route");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertEquals($response->getContent(), 'handled route');
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param TestController $controller
   *
   * @throws \Throwable
   */
  public function testAuthResponse(TestController $controller)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/route");
    $this->_prepareCubex($cubex, $request);
    $response = $controller->handle($cubex->getContext());
    self::assertInstanceOf(Response::class, $response);
    self::assertEquals(200, $response->getStatusCode());

    $authFailResponse = RedirectResponse::create('/login', 302);
    $controller->setAuthResponse($authFailResponse);
    $response = $controller->handle($cubex->getContext());
    self::assertInstanceOf(RedirectResponse::class, $response);
    self::assertEquals(302, $response->getStatusCode());
    self::assertSame($authFailResponse, $response);

    $controller->setAuthResponse(false);
    $this->expectExceptionMessage(AuthedController::ERROR_ACCESS_DENIED);
    $controller->handle($cubex->getContext());
  }

  /**
   * @throws \Throwable
   */
  public function testIncompleteController()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestIncompleteController();
    $request = HttpRequest::create("/route");
    $this->_prepareCubex($cubex, $request);
    $this->expectExceptionMessage(ConditionHandler::ERROR_NO_HANDLER);
    $controller->handle($cubex->getContext());
  }

  /**
   * @dataProvider controllersProvider
   *
   * @param TestController $controller
   *
   * @throws \Throwable
   */
  public function testPreHandleEvent(TestController $controller)
  {
    $run = null;
    $cubex = new Cubex(__DIR__, null, false);
    $request = HttpRequest::create("/subs/events");
    $this->_prepareCubex($cubex, $request);
    $cubex->getContext()->events()->listen(
      PreExecuteEvent::class,
      function (PreExecuteEvent $e) use (&$run) {
        if($run === null)
        {
          $run = $e->getHandler();
        }
      }
    );
    $controller->handle($cubex->getContext());
    self::assertInstanceOf(SubTestController::class, $run);
  }

  public function testProcessingObjectNull()
  {
    $controller = new TestController();
    $resp = null;
    self::assertFalse($controller->processObject(new Context(), null, $resp));
  }

  public function testZeroResponse()
  {
    $ctx = new Context(HttpRequest::create('/user/0'));
    $controller = new TestController();
    self::assertEquals('0', $controller->handle($ctx)->getContent());
  }
}
