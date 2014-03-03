<?php
namespace namespaced\sub;

use Cubex\Console\ConsoleCommand;
use Cubex\Kernel\ApplicationKernel;
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
