<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Kernel\ProjectKernel;

class ProjectKernelTest extends \PHPUnit_Framework_TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new ProjectKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
