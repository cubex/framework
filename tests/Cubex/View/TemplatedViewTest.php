<?php
namespace CubexTest\Cubex\View;

use Cubex\Kernel\ControllerKernel;
use Cubex\View\TemplatedView;

class TemplatedViewTest extends \PHPUnit_Framework_TestCase
{
  public function testRender()
  {
    $view = new TemplatedView(new MockTemplatedViewKernel(), 'Templated');
    $this->assertStringStartsWith('Templated View', $view->render());
  }
}

class MockTemplatedViewKernel extends ControllerKernel
{
}
