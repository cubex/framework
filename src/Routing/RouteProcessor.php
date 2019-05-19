<?php
namespace Cubex\Routing;

use Packaged\Context\Context;
use Packaged\Context\ContextAware;
use Cubex\Events\PreExecuteEvent;
use Cubex\Http\Handler;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use function class_exists;
use function is_callable;
use function is_string;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

abstract class RouteProcessor extends RouteSelector
{
  const ERROR_NO_ROUTE = "unable to handle your request";
  const ERROR_INVALID_ROUTE_RESPONSE = "unable to process your request";

  /**
   * @param Context $c
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(Context $c): Response
  {
    $response = null;
    $handler = $this->_getHandler($c);

    ob_start();
    try
    {
      $handler = $this->_prepareHandler($c, $handler);
      if(!$this->_processHandler($c, $handler, $response))
      {
        $this->_processRoute($c, $handler, $response);
      }
    }
    catch(\Throwable $e)
    {
      ob_end_clean(); //End and clear the buffer
      throw $e;
    }
    $response = $this->_prepareResponse($c, $response, ob_get_clean());

    if($response instanceof Response)
    {
      return $response;
    }
    throw new Exception(self::ERROR_INVALID_ROUTE_RESPONSE, 500);
  }

  /**
   * @param Context $c
   * @param mixed   $handler
   *
   * @return Handler|callable|mixed
   */
  protected function _prepareHandler(Context $c, $handler)
  {
    if(is_string($handler) && strpos($handler, '\\') !== false && class_exists($handler))
    {
      return new $handler();
    }
    return $handler;
  }

  /**
   * @param Context $c
   * @param         $handler
   * @param         $response
   *
   * @return bool
   * @throws Exception
   */
  protected function _processHandler(Context $c, $handler, &$response): bool
  {
    $processed = $handler !== null && !is_string($handler);
    while(is_callable($handler))
    {
      $handler = $handler($c);
    }

    if($handler instanceof ContextAware)
    {
      $handler->setContext($c);
    }

    if($handler instanceof Handler)
    {
      $c->events()->trigger(PreExecuteEvent::i($c, $handler));
      $response = $handler->handle($c);
      return true;
    }

    if($handler)
    {
      $response = $handler;
    }

    return $processed;
  }

  /**
   * If all else fails, push the route result through this method
   *
   * @param Context $c
   * @param mixed   $handler Result of the located route
   * @param         $response
   */
  protected function _processRoute(Context $c, $handler, &$response)
  {
    if($handler === null)
    {
      throw new \RuntimeException(ConditionHandler::ERROR_NO_HANDLER, 500);
    }
    throw new \RuntimeException(self::ERROR_NO_ROUTE, 404);
  }

  /**
   * @param Context           $c
   * @param mixed             $result Route response
   * @param string|null|false $buffer Output Buffer if available
   *
   * @return mixed
   */
  protected function _prepareResponse(Context $c, $result, $buffer = null)
  {
    return $result ?: ($buffer !== false ? $buffer : null);
  }
}
