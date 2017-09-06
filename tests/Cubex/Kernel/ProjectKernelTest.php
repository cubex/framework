<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Kernel\ProjectKernel;
use PHPUnit\Framework\TestCase;

class ProjectKernelTest extends TestCase
{
  public function testSubRoutesRetunsArray()
  {
    $kernel = new ProjectKernel();
    $this->assertInternalType('array', $kernel->subRouteTo());
  }
}
