<?php

namespace Cubex\Tests\Controller;

use Cubex\Context\Context;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Controller\TestSingleRoutedController;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class SingleRouteControllerTest extends TestCase
{
  public function testGetRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestSingleRoutedController();
    $request = Request::create("/route");
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('GET REQ', $response->getContent());
  }

  public function testPostRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestSingleRoutedController();
    $request = Request::create("/route", 'POST');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('POST REQ', $response->getContent());
  }

  public function testAjaxRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestSingleRoutedController();
    $request = Request::create("/route");
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('AJAX REQ', $response->getContent());
  }

  public function testDeleteRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $controller = new TestSingleRoutedController();
    $request = Request::create("/route", 'DELETE');
    $cubex->share(Context::class, new Context($request));
    $response = $controller->handle($cubex->getContext());
    $this->assertStringContainsString('PROCESS REQ', $response->getContent());
  }
}
