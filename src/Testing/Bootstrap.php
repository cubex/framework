<?php
namespace Cubex\Testing;

use Cubex\Cubex;

class Bootstrap extends CubexTestCase
{
  public function __construct(Cubex $cubex)
  {
    self::$_globalCubex = $cubex;
  }

  public function boot()
  {
  }
}
