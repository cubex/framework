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

  /**
   * Create an application with cubex set
   *
   * @param Cubex $cubex
   *
   * @param array $constructArgs
   *
   * @return $this
   */
  public static function withCubex(Cubex $cubex, ...$constructArgs)
  {
    $application = new static(...$constructArgs);
    $application->setCubex($cubex);
    return $application;
  }
}
