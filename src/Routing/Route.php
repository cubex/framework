<?php
namespace Cubex\Routing;

use Cubex\Http\Handler;

class Route extends ConditionSet implements ConditionHandler
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
}
