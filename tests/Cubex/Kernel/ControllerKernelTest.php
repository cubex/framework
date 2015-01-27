<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Kernel\ControllerKernel;

class ControllerKernelTest extends \PHPUnit_Framework_TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new ControllerKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
