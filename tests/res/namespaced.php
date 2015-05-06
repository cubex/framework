<?php
namespace namespaced;

use Cubex\Console\ConsoleCommand;
use Cubex\Kernel\CubexKernel;
use Cubex\View\LayoutController;
use Cubex\View\ViewModel;

class CubexProject extends CubexKernel
{
  public function subRouteTo()
  {
    return [
      'sub\%sApplication',
    ];
  }

  public function getRoutes()
  {
    return [
      'droute' => '\namespaced\DefaultRouteTest',
    ];
  }
}

class DefaultRouteTest extends CubexKernel
{
  public function defaultRoute()
  {
    return new DefaultActionTest();
  }
}

class DefaultActionTest extends CubexKernel
{
  public function defaultAction()
  {
    return 'processed: ' . $this->_pathProcessed;
  }

  public function getRoutes()
  {
    return ['path' => 'defaultAction'];
  }
}

class TheRoutable
{
  public function __toString()
  {
    return 'namespaced';
  }
}

/**
 * @name Namer
 */
class NamerCommand extends ConsoleCommand
{
}

class TestLayoutController extends LayoutController
{
  protected $_contentName = 'testing';

  public function renderTest()
  {
    return 'test renderTest';
  }

  public function renderEcho()
  {
    echo 'test renderEcho';
  }

  public function renderView()
  {
    return new TestViewModel();
  }

  public function renderJson()
  {
    $class = new \stdClass();
    $class->test = 'json';
    return $class;
  }
}

class TestViewModel extends ViewModel
{
  public function render()
  {
    if($this->isCubexAvailable())
    {
      return 'View Model Test';
    }
    return 'Cubex Not Available';
  }
}
