<?php
namespace Cubex\Application;

use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\Routing\ConditionSelector;

/**
 * Base Application
 */
abstract class Application extends ConditionSelector implements ContextAware
{
  use CubexAwareTrait;
  use ContextAwareTrait;

  public function __construct(Cubex $cubex)
  {
    $this->setCubex($cubex);
  }
}
