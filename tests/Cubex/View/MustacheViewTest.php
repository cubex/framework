<?php
namespace CubexTest\Cubex\View;

use Cubex\View\MustacheViewModel;

class MustacheViewTest extends \PHPUnit_Framework_TestCase
{
  public function testRender()
  {
    $view = new TestMustacheModel();
    $view->setTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'res');
    $view->setTemplateFile('mustache');
    $this->assertContains(
      'Hello Test You have just won $10 ($12)!',
      $view->render()
    );
  }

  public function testInvalidFile()
  {
    $view = new TestMustacheModel();
    $view->setTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'res');
    $view->setTemplateFile('invalid');
    $tpl = $view->getTemplatePath('.phtml');
    $this->setExpectedException(
      'Exception',
      'The template file \'' . $tpl . '\' does not exist'
    );
    $view->render();
  }
}

class TestMustacheModel extends MustacheViewModel
{
  public $name = 'Test';
  public $value = 10;

  public function taxValue()
  {
    return $this->value * 1.2;
  }
}
