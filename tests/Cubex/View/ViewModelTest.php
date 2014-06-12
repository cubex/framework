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
}
