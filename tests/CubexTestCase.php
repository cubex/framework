<?php
abstract class CubexTestCase extends PHPUnit_Framework_TestCase
{
  /**
   * @return \Cubex\Cubex
   */
  public function newCubexInstace()
  {
    return (new \Cubex\Cubex(__DIR__))
      ->configure(
        (new \Packaged\Config\Provider\Test\TestConfigProvider())
          ->addSection(
            new \Packaged\Config\Provider\Test\TestConfigSection("kernel")
          )
          ->addSection(
            new \Packaged\Config\Provider\Test\TestConfigSection("routing")
          )
          ->addSection(
            new \Packaged\Config\Provider\Test\TestConfigSection("response")
          )
          ->addSection(
            new \Packaged\Config\Provider\Test\TestConfigSection("errors")
          )
      );
  }
}
