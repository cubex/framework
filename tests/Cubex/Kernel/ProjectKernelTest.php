<?php

class ProjectKernelTest extends PHPUnit_Framework_TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new \Cubex\Kernel\ProjectKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
