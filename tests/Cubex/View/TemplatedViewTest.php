<?php
namespace Cubex\View;

use Cubex\Kernel\ControllerKernel;

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
