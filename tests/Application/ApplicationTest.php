<?php

namespace Cubex\Tests\Application;

use Cubex\Cubex;
use Cubex\Events\Handle\ResponsePrepareEvent;
use Cubex\Tests\Supporting\Application\TestApplication;
use Cubex\Tests\Supporting\Application\TestApplicationDefaultHandler;
use Cubex\Tests\Supporting\Application\TestExtendedApplication;
use Packaged\Context\Context;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
  public function testWithCubex()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $app = new TestExtendedApplication($cubex);
    $this->assertEquals($cubex, $app->getCubex());
    $this->assertTrue($app->hasCubex());
    $app->clearCubex();
    $this->assertFalse($app->hasCubex());
    $this->assertNull($app->getCubex());

    $response = $app->handle($cubex->getContext());
    $this->assertContains("App Default", $response->getContent());
  }

  public function testDefaultHandler()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $app = new TestApplicationDefaultHandler($cubex);
    $response = $app->handle($cubex->getContext());
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertContains("Not Found", $response->getContent());

    $request = Request::create("/some-route");
    $ctx = new Context($request);
    $cubex->share(Context::class, $ctx);
    $response = $cubex->handle($app, false);
    $this->assertEquals(404, $response->getStatusCode());
    $this->assertContains("Not Found", $response->getContent());
  }

  public function testRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $app = new TestApplication($cubex);
    $request = Request::create("/buffered");
    $ctx = new Context($request);
    $cubex->listen(
      ResponsePrepareEvent::class,
      function (ResponsePrepareEvent $e) {
        $this->assertInstanceOf(TestApplication::class, $e->getHandler());
      }
    );
    $cubex->share(Context::class, $ctx);
    $response = $cubex->handle($app, false);
    $this->assertStringContainsString('BUFFER', $response->getContent());
  }
}
