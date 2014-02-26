<?php
namespace namespaced;

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
