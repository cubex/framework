<?php
namespace Cubex\Kernel;

class ProjectKernel extends CubexKernel
{
  public function subRouteTo()
  {
    return [
      'Applications\%s\%sApplication',
      'Applications\%s\%sApp',
      'Applications\%sApplication',
      'Applications\%sApp',
      '%sApplication',
      '%sApp',
      '%s',
    ];
  }
}
