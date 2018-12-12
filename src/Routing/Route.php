<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Http\Handler;

class Route implements ConditionHandler
{
  private $_result;
  protected $_matchAnything = false;
  protected $_conditions = [];

  public static function with(Condition $condition)
  {
    $cond = new static();
    $cond->_conditions = [$condition];
    return $cond;
  }

  public function add(Condition $condition)
  {
    $this->_conditions[] = $condition;
    return $this;
  }

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

  public function match(Context $context): bool
  {
    foreach($this->_conditions as $condition)
    {
      if(!$condition->match($context))
      {
        return false;
      }
    }
    return true;
  }

}
