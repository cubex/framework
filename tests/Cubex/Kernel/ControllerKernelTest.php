<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Kernel\ControllerKernel;
use PHPUnit\Framework\TestCase;

class ControllerKernelTest extends TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new ControllerKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
