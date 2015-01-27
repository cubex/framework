<?php
namespace CubexTest\Cubex\Facade;

use Cubex\Facade\FacadeLoader;

class FacadeLoaderTest extends \PHPUnit_Framework_TestCase
{
  public function testLoad()
  {
    FacadeLoader::register();
    $this->assertFalse(class_exists('CubexTestFacadeLoad'));
    FacadeLoader::addAlias('CubexTestFacadeLoad', '\Cubex\CubexException');
    $this->assertTrue(class_exists('CubexTestFacadeLoad'));
  }
}
