<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Kernel\ApplicationKernel;

class ApplicationKernelTest extends \PHPUnit_Framework_TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new ApplicationKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
