<?php
namespace CubexTest\Cubex\View;

use Cubex\View\Renderable;

class RenderableTest extends \PHPUnit_Framework_TestCase
{
  public function testRenderable()
  {
    $render = new Renderable('hello');
    $this->assertInstanceOf(
      'Illuminate\Support\Contracts\RenderableInterface',
      $render
    );
    $this->assertEquals('hello', $render->render());
    $this->assertEquals('hello', (string)$render);
  }
}
