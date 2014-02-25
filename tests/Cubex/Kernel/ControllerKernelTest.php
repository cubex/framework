<?php

class ControllerKernelTest extends PHPUnit_Framework_TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new \Cubex\Kernel\ControllerKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
