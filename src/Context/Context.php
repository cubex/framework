<?php
namespace Cubex\Context;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;

class Context extends \Packaged\Context\Context implements CubexAware
{
  use CubexAwareTrait;

  public function logger()
  {
    return $this->getCubex()->getLogger();
  }
}
