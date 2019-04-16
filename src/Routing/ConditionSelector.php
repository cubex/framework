<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Http\Handler;
use Symfony\Component\HttpFoundation\Response;

abstract class ConditionSelector implements Handler
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
  protected function _getHandler(Context $context)
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

  /**
   * @param                         $path
   * @param string|callable|Handler $result
   *
   * @return Route
   */
  protected static function _route($path, $result)
  {
    return Route::with(RequestConstraint::i()->path($path))->setHandler($result);
  }

  public function handle(Context $c): Response
  {
    $handler = $this->_getHandler($c);
    if($handler instanceof Handler)
    {
      return $handler->handle($c);
    }
    throw new \RuntimeException(ConditionHandler::ERROR_NO_HANDLER, 500);
  }

}
