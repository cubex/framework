<?php

class ViewModelTest extends \PHPUnit_Framework_TestCase
{
  public function testGetSet()
  {
    $view = $this->getMockForAbstractClass('\Cubex\View\ViewModel');
    /**
     * @var $view \Cubex\View\ViewModel
     */

    $view->setTemplateDir('randomDir');
    $this->assertEquals('randomDir', $view->getTemplateDir());

    $view->setTemplateFile('randomFile');
    $this->assertEquals('randomFile', $view->getTemplateFile());

    $this->assertEquals(
      build_path('randomDir', 'randomFile.phtml'),
      $view->getTemplatePath('.phtml')
    );
  }

  public function testCalculateLocation()
  {
    $view = new namespaced\Views\TestableView();
    /**
     * @var $view \Cubex\View\ViewModel
     */

    $view->getTemplateFile();
    $view->setTemplateDir(null);
    $this->assertStringEndsWith('Templates', $view->getTemplateDir());
    $this->assertEquals('TestableView', $view->getTemplateFile());

    $view = new namespaced\sub\TestView();
    /**
     * @var $view \Cubex\View\ViewModel
     */

    $view->getTemplateFile();
    $view->setTemplateDir(null);
    $this->assertStringEndsWith('Templates', $view->getTemplateDir());
    $this->assertEquals('TestView', $view->getTemplateFile());
  }

  public function testToString()
  {
    $viewModel = new RenderableViewModel();
    $this->assertEquals('rendered', (string)$viewModel);

    $exception = 'Render Exception';
    $viewModel = new RenderableViewModel($exception);
    $expect    = '<h1>An uncaught exception was thrown</h1>';
    $this->assertContains($expect, (string)$viewModel);
  }
}

class RenderableViewModel extends \Cubex\View\ViewModel
{
  protected $_exception;

  public function __construct($exception = null)
  {
    $this->_exception = $exception;
  }

  public function render()
  {
    if($this->_exception !== null)
    {
      throw new Exception($this->_exception);
    }
    return 'rendered';
  }
}
