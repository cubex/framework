<?php
namespace Cubex\Kernel;

class ApplicationKernel extends CubexKernel
{
  public function subRouteTo()
  {
    return [
      'Controllers\%s\%sController',
      'Controllers\%sController',
      'Controllers\%s\%s',
      'Controllers\%s',
      '%sController',
      '%s',
    ];
  }
}
