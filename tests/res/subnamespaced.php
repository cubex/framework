<?php
namespace namespaced\sub;

use Cubex\Console\ConsoleCommand;
use Cubex\Kernel\ApplicationKernel;
use Cubex\Kernel\CubexKernel;
use Cubex\View\ViewModel;

class SubRoutable
{
  public function __toString()
  {
    return 'namespaced sub';
  }
}

class TestApplication extends ApplicationKernel
{
  public function subRouteTo()
  {
    return [
      '%sExtension',
    ];
  }

  public function defaultAction()
  {
    return 'test application';
  }
}

class RandomExtension extends ApplicationKernel
{
  public function renderTags($tagName)
  {
    return 'test tag ' . $tagName;
  }

  public function defaultAction($base = 'test', $type = 'extension')
  {
    return "$base $type";
  }
}

class ManualRouteExtension extends ApplicationKernel
{
  public function defaultAction($base = 'test', $type = 'extension')
  {
    return "$base $type";
  }

  public function doShow($id1, $id2)
  {
    return 'showing manual route for ' . $id1 . ' ' . $id2;
  }

  public function myCallback()
  {
    return 'callback route';
  }

  public function getRoutes()
  {
    return [
      'first-path/:id1@num'               => [
        'show/:id2@num' => 'doShow'
      ],
      'manual-route/second-path/:id1@num' => [
        'show/:id2@num' => 'doShow'
      ],
      'cb'                                => [$this, 'myCallback']
    ];
  }
}

class DefaultExtension extends CubexKernel
{
  public function defaultRoute()
  {
    return '\namespaced\sub\TestSubController';
  }

  public function pathTest($arg)
  {
    return $arg;
  }

  public function getRoutes()
  {
    return [
      'pathnum/:id@num'     => 'pathTest',
      'pathalpha/:id@alpha' => 'pathTest',
      'pathall/:id@all'     => 'pathTest',
      'path/:id'            => 'pathTest',
    ];
  }
}

class TestSubController extends CubexKernel
{
  public function defaultAction()
  {
    return 'test default action';
  }

  public function testSubRoute()
  {
    return 'test sub route';
  }
}

class TestView extends ViewModel
{
  public function render()
  {
    return 'testable';
  }
}

/**
 * @name Hidden
 */
class HiddenCommand extends ConsoleCommand
{
}
