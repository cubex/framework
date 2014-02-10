<?php

class CubexExceptionTest extends PHPUnit_Framework_TestCase
{
  public function testDebug()
  {
    $exception = new \Cubex\CubexException();
    $exception->setDebug("hi");
    $this->assertEquals("hi", $exception->getDebug());
  }

  public function testStaticConstructor()
  {
    $exception = \Cubex\CubexException::debugException("msg", 123, "debug");
    $this->assertEquals("msg", $exception->getMessage());
    $this->assertEquals(123, $exception->getCode());
    $this->assertEquals("debug", $exception->getDebug());
  }
}
