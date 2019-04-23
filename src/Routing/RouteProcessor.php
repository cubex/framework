<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Events\PreExecuteEvent;
use Cubex\Http\Handler;
use Exception;
use Symfony\Component\HttpFoundation\Response;

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
    $processed = false; //Flag for if the handler has been processed
    $handler = $this->_getHandler($c);

    ob_start();
    try
    {
      if(is_object($handler))
      {
        $c->events()->trigger(PreExecuteEvent::i($c, $handler));
        $processed = $this->_processMixed($c, $handler, $response);
      }
      else if(is_callable($handler))
      {
        $c->events()->trigger(PreExecuteEvent::i($c, $handler));
        $processed = $this->_processMixed($c, $handler(), $response);
      }
      else if(is_string($handler))
      {
        $processed = $this->_processStringHandler($c, $handler, $response);
      }

      if(!$processed)
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
   * @param Context $c
   * @param string  $handler
   *
   * @param         $response
   *
   * @return bool
   * @throws Exception
   */
  protected function _processStringHandler(Context $c, string $handler, &$response): bool
  {
    if(strstr($handler, '\\') && class_exists($handler))
    {
      $handlerObj = new $handler();
      $c->events()->trigger(PreExecuteEvent::i($c, $handlerObj));
      return $this->_processMixed($c, $handlerObj, $response);
    }
    return false;
  }

  /**
   * @param Context $c
   * @param         $handler
   * @param         $response
   *
   * @return bool
   * @throws Exception
   */
  protected function _processMixed(Context $c, $handler, &$response): bool
  {
    if($handler instanceof ContextAware)
    {
      $handler->setContext($c);
    }

    if($handler instanceof Handler)
    {
      $response = $handler->handle($c);
      return true;
    }

    if($handler !== null)
    {
      $response = $handler;
      return true;
    }

    return false;
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
