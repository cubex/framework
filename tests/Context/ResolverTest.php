<?php

namespace Cubex\Tests\Context;

use Cubex\Context\Context as CubexContext;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Controller\TestResolvableController;
use Packaged\Context\Context;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
  public static Cubex $cubex;

  public static function setUpBeforeClass(): void
  {
    self::$cubex = new Cubex(__DIR__, null, false);
    parent::setUpBeforeClass();
  }

  protected function _prepareCubex(Cubex $cubex, Request $request)
  {
    $ctx = new CubexContext($request);
    $ctx->setCubex($cubex);
    $cubex->share(Context::class, $ctx);
  }

  public function testConstructorResolver()
  {
    $cubex = self::$cubex;
    $request = Request::create('/');
    $this->_prepareCubex($cubex, $request);

    $controller = $cubex->resolve(TestResolvableController::class);
    $controller->setCubex($cubex);

    // expect controller to be constructed with the context
    self::assertInstanceOf(TestResolvableController::class, $controller);
    self::assertInstanceOf(Context::class, $controller->getCustomContext());
  }

  public function testMethodResolver()
  {
    $cubex = self::$cubex;
    $request = Request::create('/');
    $this->_prepareCubex($cubex, $request);

    $controller = $cubex->resolve(TestResolvableController::class);
    $controller->setCubex($cubex);

    $response = $controller->handle($cubex->getContext());
    self::assertEquals('Default name', $response->getContent());
  }
}
