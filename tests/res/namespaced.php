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
    $class       = new \stdClass();
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
