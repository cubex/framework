<?php

class CubexKernelTest extends PHPUnit_Framework_TestCase
{
  public function getKernel()
  {
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    $kernel->setCubex(new \Cubex\Cubex());
    return $kernel;
  }

  public function testCubexAwareGetNull()
  {
    $this->setExpectedException('RuntimeException');
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    $kernel->getCubex();
  }

  public function testCubexAwareSetGet()
  {
    $kernel = $this->getKernel();
    $cubex  = new \Cubex\Cubex();
    $kernel->setCubex($cubex);
    $this->assertSame($kernel->getCubex(), $cubex);
  }

  public function testHandle()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $kernel = $this->getKernel();
    $resp = $kernel->handle($request);
    $this->assertInstanceOf('\Cubex\Http\Response', $resp);
  }

  public function testInit()
  {
    $kernel = $this->getKernel();
    $kernel->init();
  }
}
