<?php
namespace Cubex\Routing;

use Cubex\Http\Handler;

interface ConditionHandler extends Condition
{
  /**
   * @return Handler|string|callable
   */
  public function getHandler();
}
