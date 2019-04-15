<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Http\Handler;

abstract class ConditionSelector
{
  /**
   * @return Route[]|\Generator|string|Handler|callable
   */
  abstract protected function _getConditions();

  /**
   * @param Context $context
   *
   * @return Handler|string|callable
   */
  public function getHandler(Context $context)
  {
    $conditions = $this->_getConditions();
    if($conditions instanceof \Traversable || is_array($conditions))
    {
      foreach($conditions as $route)
      {
        if($route instanceof Route && $route->match($context))
        {
          return $route->getHandler();
        }
      }

      if($conditions instanceof \Generator)
      {
        return $conditions->getReturn();
      }
    }
    else if($conditions instanceof Handler || is_callable($conditions) || is_string($conditions))
    {
      return $conditions;
    }

    return null;
  }
}
