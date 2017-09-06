<?php
namespace CubexTest\Cubex\View;

use Cubex\View\ArrayTemplate;
use PHPUnit\Framework\TestCase;

class ArrayTemplateTest extends TestCase
{
  public function testBasics()
  {
    $tpl = new ArrayTemplate();
    $tpl->setTemplate('%s-%s');
    $tpl->setGlue(',');
    $tpl[] = ['a', 'b'];
    $tpl[] = ['c', 'd'];
    $this->assertEquals('a-b,c-d', $tpl->render());
  }

  public function testStatic()
  {
    $tpl = ArrayTemplate::create('%s-%s', ',', [['a', 'b'], ['c', 'd']]);
    $this->assertEquals('a-b,c-d', $tpl->render());
  }

  public function testItem()
  {
    $tpl = new ArrayTemplate();
    $tpl->setTemplate('%s = %s');
    $this->assertEquals('a = b', $tpl->renderItem(['a', 'b']));
  }
}
