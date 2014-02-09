<?php
include_once(__DIR__ . '/CubexKernelTest.php');

class ProjectKernelTest extends CubexKernelTest
{
  public function getKernel()
  {
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\ProjectKernel');
    $kernel->setCubex(new \Cubex\Cubex());
    return $kernel;
  }

  public function testExtendsCubexKernel()
  {
    $kernel = $this->getKernel();
    $this->assertInstanceOf('\Cubex\Kernel\CubexKernel', $kernel);
  }
}
