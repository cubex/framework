<?php
namespace View;

use Cubex\View\Layout;
use Illuminate\Support\Contracts\RenderableInterface;
use namespaced\CubexProject;

class LayoutTest extends \PHPUnit_Framework_TestCase
{
  public function testSections()
  {
    $section = new RenderableSection();
    $layout  = new Layout(new CubexProject(), 'Default');

    $this->assertFalse($layout->exists('first'));

    $layout->insert($section, 'first');
    $this->assertTrue($layout->exists('first'));
    $this->assertEquals('section', $layout->first());

    $layout->remove('first');
    $this->assertFalse($layout->exists('first'));
    $this->assertNull($layout->first());
  }

  public function testRender()
  {
    $layout = new Layout(new CubexProject(), 'Default');
    $layout->insert(new RenderableSection(), 'testing');
    $rendered = $layout->render();
    $this->assertContains('Testing', $rendered);
    $this->assertContains('<pre>section</pre>', $rendered);
  }
}

class RenderableSection implements RenderableInterface
{
  public function render()
  {
    return 'section';
  }
}
