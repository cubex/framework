<?php
namespace CubexTest\Cubex\Testing;

use Cubex\Cubex;
use Cubex\Testing\Bootstrap;
use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
  public function testSetsGlobal()
  {
    $cubex = new Cubex();
    $bootstrap = new Bootstrap($cubex);
    $bootstrap->boot();
    $this->assertSame($cubex, $bootstrap->getCubex());
  }

  public function testReplaceCubexPerInstance()
  {
    $cubex = new Cubex();
    $cubex2 = new Cubex();
    $bootstrap = new Bootstrap($cubex);
    $bootstrap->boot();
    $this->assertSame($cubex, $bootstrap->getCubex());
    $this->assertNotSame($cubex2, $bootstrap->getCubex());
    $bootstrap->setCubex($cubex2);
    $this->assertSame($cubex2, $bootstrap->getCubex());
  }
}
