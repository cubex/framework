<?php

namespace Cubex\Tests\Controller;

use Cubex\Context\Context as CubexContext;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Controller\TestViewModelController;
use Cubex\ViewModel\JsonView;
use Packaged\Context\Context;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ViewModelControllerTest extends TestCase
{
  protected function _prepareCubex(Cubex $cubex, Request $request)
  {
    $ctx = new CubexContext($request);
    $ctx->setCubex($cubex);
    $cubex->share(Context::class, $ctx);
  }

  /**
   * Test we can render just a view, without a model
   *
   * @return void
   * @throws \Throwable
   */
  public function testView()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create('/');
    $this->_prepareCubex($cubex, $request);

    $controller = new TestViewModelController();
    $controller->setCubex($cubex);

    $response = $controller->handle($cubex->getContext());

    self::assertStringContainsString('<h1>Default View</h1>', $response->getContent());
  }

  public function testViewModel()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create('/test');
    $this->_prepareCubex($cubex, $request);

    $controller = new TestViewModelController();
    $controller->setCubex($cubex);

    $response = $controller->handle($cubex->getContext());

    self::assertStringContainsString('<h1>Test View</h1>', $response->getContent());
  }

  public function testViewModelWithDataChange()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create('/test-data');
    $this->_prepareCubex($cubex, $request);

    $controller = new TestViewModelController();
    $controller->setCubex($cubex);

    $response = $controller->handle($cubex->getContext());

    self::assertStringContainsString('<h1>Test Data View</h1>', $response->getContent());
  }

  public function testViewModelWithJSONRender()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $request = Request::create('/test-data');
    $this->_prepareCubex($cubex, $request);

    $controller = new TestViewModelController();
    $controller->setCubex($cubex);
    $controller->setDefaultView(JsonView::class);

    $response = $controller->handle($cubex->getContext());

    // remove linebreaks
    $responseContent = preg_replace('/\s+/', '', $response->getContent());
    self::assertStringContainsString('{"test":"TestData"}', $responseContent);
  }

}
