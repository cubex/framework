<?php
namespace Cubex\Context;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Psr\Log\LoggerInterface;

class Context extends \Packaged\Context\Context implements CubexAware
{
  use CubexAwareTrait;

  public function log(): LoggerInterface
  {
    return $this->getCubex()->getLogger();
  }
}
