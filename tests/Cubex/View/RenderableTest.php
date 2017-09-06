<?php
namespace CubexTest\Cubex\View;

use Cubex\View\Renderable;
use PHPUnit\Framework\TestCase;

class RenderableTest extends TestCase
{
  public function testRenderable()
  {
    $render = new Renderable('hello');
    $this->assertInstanceOf(
      'Illuminate\Contracts\Support\Renderable',
      $render
    );
    $this->assertEquals('hello', $render->render());
    $this->assertEquals('hello', (string)$render);
  }
}
