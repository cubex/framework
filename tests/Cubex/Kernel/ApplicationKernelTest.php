<?php

class ApplicationKernelTest extends PHPUnit_Framework_TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new \Cubex\Kernel\ApplicationKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
