<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Kernel\ApplicationKernel;
use PHPUnit\Framework\TestCase;

class ApplicationKernelTest extends TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new ApplicationKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
