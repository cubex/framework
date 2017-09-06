<?php
namespace CubexTest\Cubex\Facade;

use Cubex\Facade\FacadeLoader;
use PHPUnit\Framework\TestCase;

class FacadeLoaderTest extends TestCase
{
  public function testLoad()
  {
    FacadeLoader::register();
    $this->assertFalse(class_exists('CubexTestFacadeLoad'));
    FacadeLoader::addAlias('CubexTestFacadeLoad', '\Cubex\CubexException');
    $this->assertTrue(class_exists('CubexTestFacadeLoad'));
  }
}
