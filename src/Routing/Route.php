<?php
namespace Cubex\Routing;

use Packaged\Context\Context;
use Cubex\Http\Handler;

class Route extends ConditionSet implements ConditionHandler, RouteCompleter
{
  private $_result;

  public function getHandler()
  {
    return $this->_result;
  }

  /**
   * @param Handler|string|callable $handler
   *
   * @return Route
   */
  public function setHandler($handler)
  {
    $this->_result = $handler;
    return $this;
  }

  public function complete(Context $context)
  {
    foreach($this->_conditions as $condition)
    {
      if($condition instanceof RouteCompleter)
      {
        $condition->complete($context);
      }
    }
  }

}
