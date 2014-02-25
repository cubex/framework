<?php
namespace Cubex\Kernel;

class ControllerKernel extends CubexKernel
{
  public function subRouteTo()
  {
    return [
      'Views\%sView',
      'Views\%s',
      '%sView',
      '%s',
    ];
  }
}
