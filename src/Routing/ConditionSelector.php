<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Http\Handler;
use Generator;
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
      if($final instanceof Generator)
      {
        return $this->_traverseConditions($context, $final);
      }

      return $final;
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
    if(is_string($handler) && strstr($handler, '\\') && class_exists($handler))
    {
      $handler = new $handler();
    }

    if($handler instanceof Handler)
    {
      return $handler->handle($c);
    }

    throw new \RuntimeException(ConditionHandler::ERROR_NO_HANDLER, 500);
  }

}
