<?php

namespace Cubex\Tests\Application;

use Cubex\Cubex;
use Cubex\Tests\Supporting\Application\TestApplication;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
  public function testWithCubex()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $app = TestApplication::withCubex($cubex);
    $this->assertEquals($cubex, $app->getCubex());
    $this->assertTrue($app->hasCubex());
    $app->clearCubex();
    $this->assertFalse($app->hasCubex());
    $this->assertNull($app->getCubex());
  }
}
