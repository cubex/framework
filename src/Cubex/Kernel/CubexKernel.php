<?php
namespace Cubex\Kernel;

use Cubex\CubexAwareTrait;
use Cubex\ICubexAware;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class CubexKernel implements HttpKernelInterface, ICubexAware
{
  use CubexAwareTrait;

  /**
   * Initialise component
   */
  public function init()
  {
  }
}
