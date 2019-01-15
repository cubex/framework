<?php
namespace Cubex\Tests\Supporting\Ui\TestElement;

use Composer\Autoload\ClassLoader;

class TestFakeLoader extends ClassLoader
{
  public function findFile($class)
  {
    return __DIR__ . '/FakeTestUiElement.php';
  }
}
