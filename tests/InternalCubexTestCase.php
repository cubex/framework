<?php
namespace CubexTest;

use Cubex\Cubex;
use Packaged\Config\Provider\Test\TestConfigProvider;
use Packaged\Config\Provider\Test\TestConfigSection;
use PHPUnit\Framework\TestCase;

abstract class InternalCubexTestCase extends TestCase
{
  /**
   * @return \Cubex\Cubex
   */
  public function newCubexInstace()
  {
    return (new Cubex(__DIR__))
      ->configure(
        (new TestConfigProvider())
          ->addSection(
            new TestConfigSection("kernel")
          )
          ->addSection(
            new TestConfigSection("routing")
          )
          ->addSection(
            new TestConfigSection("response")
          )
          ->addSection(
            new TestConfigSection("errors")
          )
      );
  }
}
