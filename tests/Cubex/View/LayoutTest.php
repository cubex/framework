<?php
namespace CubexTest\Cubex\View;

use Cubex\View\Layout;
use Illuminate\Contracts\Support\Renderable;
use namespaced\CubexProject;
use PHPUnit\Framework\TestCase;

class LayoutTest extends TestCase
{
  public function testSections()
  {
    $section = new RenderableSection();
    $layout = new Layout(new CubexProject(), 'Default');

    $this->assertFalse($layout->exists('first'));

    $layout->insert('first', $section);
    $this->assertTrue($layout->exists('first'));
    $this->assertEquals('section', $layout->first());
    $this->assertEquals($section, $layout->get('first'));

    $layout->remove('first');
    $this->assertFalse($layout->exists('first'));
    $this->assertNull($layout->first());
  }

  public function testInvalidSectionGet()
  {
    $this->expectException(
      'Exception',
      "missing has not yet been bound to this layout"
    );
    $layout = new Layout(new CubexProject(), 'Default');
    $layout->get('missing');
  }

  public function testRender()
  {
    $layout = new Layout(new CubexProject(), 'Default');
    $layout->insert('testing', new RenderableSection());
    $rendered = $layout->render();
    $this->assertContains('Testing', $rendered);
    $this->assertContains('<pre>section</pre>', $rendered);
  }

  public function testSetCallingClass()
  {
    $layout = new Layout(new CubexProject(), 'Default');
    $layout->setCallingClass('namespaced\CubexProject');
    $layout->insert('testing', new RenderableSection());
    $rendered = $layout->render();
    $this->assertContains('Testing', $rendered);
    $this->assertContains('<pre>section</pre>', $rendered);
  }

  public function testData()
  {
    $layout = new Layout(new CubexProject(), 'Default');
    $layout->setData('rand', 'test');
    $this->assertEquals('test', $layout->getData('rand'));
    $this->assertEquals('tested', $layout->getData('missing', 'tested'));
  }
}

class RenderableSection implements Renderable
{
  public function render()
  {
    return 'section';
  }
}
