<?php

namespace Cubex\Tests\Application;

use Cubex\Context\Context;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Application\TestApplication;
use Cubex\Tests\Supporting\Application\TestExtendedApplication;
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

  public function testRoute()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $app = new TestApplication($cubex);
    $request = Request::create("/buffered");
    $ctx = new Context($request);
    $ctx->setCubex($cubex);
    $cubex->share(Context::class, $ctx);
    $response = $app->handle($cubex->getContext());
    $this->assertStringContainsString('BUFFER', $response->getContent());
  }
}
