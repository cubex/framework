<?php
namespace Cubex\Routing;

use Cubex\Http\Handler;

interface ConditionHandler extends Condition
{
  const ERROR_NO_HANDLER = 'No handler was available to process your request';

  /**
   * @return Handler|string|callable
   */
  public function getHandler();
}
