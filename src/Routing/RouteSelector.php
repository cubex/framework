<?php
namespace Cubex\Routing;

use Cubex\Http\Handler;
use Generator;
use Packaged\Context\Context;
use function is_callable;
use function is_iterable;
use function is_string;

abstract class RouteSelector implements Handler
{
  /**
   * @param string                  $path
   * @param string|callable|Handler $result
   *
   * @return Route
   */
  protected static function _route($path, $result)
  {
    return Route::with(RequestConstraint::i()->path($path))->setHandler($result);
  }

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

  /**
   * @return Route[]|Generator|Handler|string|callable
   */
  abstract protected function _generateRoutes();

  /**
   * @param Context  $context
   * @param iterable $conditions
   *
   * @return callable|Handler|string|null
   */
  private function _traverseConditions(Context $context, iterable $conditions)
  {
    foreach($conditions as $route)
    {
      if($route instanceof Route && $route->match($context))
      {
        if($route instanceof RouteCompleter)
        {
          $route->complete($context);
        }
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
}
