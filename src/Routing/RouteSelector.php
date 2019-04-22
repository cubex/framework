<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Http\Handler;
use Generator;

abstract class RouteSelector implements Handler
{
  /**
   * @return Route[]|Generator|Handler|string|callable
   */
  abstract protected function _generateRoutes();

  /**
   * @param Context $context
   *
   * @return Handler|string|callable
   */
  protected function _getHandler(Context $context)
  {
    $conditions = $this->_generateRoutes();
    if(is_iterable($conditions))
    {
      return $this->_traverseConditions($context, $conditions);
    }
    else if($conditions instanceof Handler || is_callable($conditions) || is_string($conditions))
    {
      return $conditions;
    }

    return null;
  }

  private function _traverseConditions(Context $context, iterable $conditions)
  {
    foreach($conditions as $route)
    {
      if($route instanceof Route && $route->match($context))
      {
        return $route->getHandler();
      }
    }

    if($conditions instanceof Generator)
    {
      $final = $conditions->getReturn();
      return $final instanceof Generator ? $this->_traverseConditions($context, $final) : $final;
    }

    return null;
  }

  /**
   * @param string         $path
   * @param string|Handler $result
   *
   * @return Route
   */
  protected static function _route($path, $result)
  {
    return Route::with(RequestConstraint::i()->path($path))->setHandler($result);
  }
}
