<?php
namespace CubexTest\Cubex\View;

use Cubex\View\TemplatedViewModel;

class TemplatedViewModelTest extends \PHPUnit_Framework_TestCase
{
  public function testRender()
  {
    $view = $this->getMockForAbstractClass('\Cubex\View\TemplatedViewModel');
    /**
     * @var $view TemplatedViewModel
     */
    $view->setTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'res');
    $view->setTemplateFile('test');
    $this->assertContains('Test phtml file', $view->render());

    $this->setExpectedException('Exception', 'Excepted');
    $view->setTemplateFile('exceptional');
    $view->render();
  }

  public function testInvalidFile()
  {
    $view = $this->getMockForAbstractClass('\Cubex\View\TemplatedViewModel');
    /**
     * @var $view TemplatedViewModel
     */
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
