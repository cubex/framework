<?php
namespace namespaced;

use Cubex\Console\ConsoleCommand;
use Cubex\Kernel\CubexKernel;

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
