<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Events\PreExecuteEvent;
use Cubex\Http\Handler;
use Cubex\Routing\Route;
use Cubex\Routing\RouteSelector;
use Exception;
use Packaged\Helpers\Strings;
use Packaged\Http\Request;
use Packaged\Http\Response as CubexResponse;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller extends RouteSelector implements Handler, ContextAware
{
  use ContextAwareTrait;

  const ERROR_ACCESS_DENIED = "you are not permitted to access this url";
  const ERROR_NO_ROUTE = "unable to handle your request";
  const ERROR_INVALID_ROUTE_RESPONSE = "unable to process your request";

  protected $_callStartTime;

  /**
   * Is this request permitted to process
   *
   * @return bool|Response
   */
  public function canProcess()
  {
    return true;
  }

  protected function _shouldThrowEventExceptions()
  {
    return true;
  }

  /**
   * @param Context $c
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(Context $c): Response
  {
    $this->setContext($c);
    //Verify the request can be processed
    $authResponse = $this->canProcess();
    if($authResponse instanceof Response)
    {
      return $authResponse;
    }
    else if($authResponse !== true)
    {
      throw new \Exception(self::ERROR_ACCESS_DENIED, 403);
    }

    $handler = $this->_getHandler($c);

    $this->_callStartTime = microtime(true);

    if($handler instanceof Handler)
    {
      $c->events()->trigger(PreExecuteEvent::i($c, $handler), $this->_shouldThrowEventExceptions());
      return $handler->handle($c);
    }

    if(is_callable($handler))
    {
      return $this->_executeCallable($c, $handler);
    }

    if($handler !== null && is_string($handler))
    {
      if(strstr($handler, '\\') && class_exists($handler))
      {
        return $this->_executeClass($c, $handler);
      }

      if(([$result, $ok] = $this->_executeMethod($c, $handler)) && $ok)
      {
        return $result;
      }
    }

    $c->events()->trigger(PreExecuteEvent::i($c, $handler), $this->_shouldThrowEventExceptions());
    return $this->_processRoute($handler);
  }

  /**
   * If all else fails, push the route result through this method
   *
   * @param mixed $routeResult Result of the located route
   */
  protected function _processRoute($routeResult)
  {
    throw new \RuntimeException(self::ERROR_NO_ROUTE, 404);
  }

  protected function _bindContext($object)
  {
    if($object instanceof ContextAware)
    {
      $object->setContext($this->getContext());
    }
    return $object;
  }

  /**
   * Yield methods to attempt
   *
   * @param Request $r
   * @param string  $method
   *
   * @return \Generator|null
   */
  protected function _getMethod(Request $r, string $method)
  {
    $method = ucfirst($method);
    if($r->isXmlHttpRequest())
    {
      yield 'ajax' . $method;
      yield 'ajax' . ucfirst(strtolower($r->getMethod())) . $method;
    }

    yield strtolower($r->getMethod()) . $method;
    yield 'process' . $method; //support for all methods
    return null;
  }

  /**
   * @param Context $c
   * @param string  $methodSuffix
   *
   * @return array [value, bool processed]
   * @throws \Throwable
   */
  protected function _executeMethod(Context $c, string $methodSuffix)
  {
    foreach($this->_getMethod($c->request(), $methodSuffix) as $attemptMethod)
    {
      if(method_exists($this, $attemptMethod))
      {
        return [$this->_executeCallable($c, [$this, $attemptMethod]), true];
      }
    }
    return [null, false];
  }

  /**
   * @param Context $c
   * @param         $callable
   *
   * @return Response
   * @throws \Throwable
   */
  protected function _executeCallable(Context $c, $callable): Response
  {
    ob_start();
    try
    {
      $c->events()->trigger(PreExecuteEvent::i($c, $callable), $this->_shouldThrowEventExceptions());
      $callableResponse = $callable();
    }
    catch(\Throwable $e)
    {
      ob_end_clean();
      throw $e;
    }
    return $this->_handleAndPrepareResponse($c, $callableResponse, ob_get_clean());
  }

  /**
   * @param Context $c
   * @param         $result
   *
   * @return Response
   * @throws Exception
   */
  protected function _executeClass(Context $c, $result): Response
  {
    $obj = new $result();
    $c->events()->trigger(PreExecuteEvent::i($c, $obj), $this->_shouldThrowEventExceptions());
    return $this->_handleAndPrepareResponse($c, $obj);
  }

  /**
   * @param Context     $c
   * @param             $response
   * @param string|null $buffer
   *
   * @return Response
   * @throws Exception
   */
  protected function _handleResponse(Context $c, $response, ?string $buffer = null): Response
  {
    if($response === null)
    {
      $response = $buffer;
    }

    if($response instanceof Handler)
    {
      $c->events()->trigger(PreExecuteEvent::i($c, $response), $this->_shouldThrowEventExceptions());
      return $response->handle($c);
    }

    if($response instanceof Response)
    {
      return $response;
    }

    if($response instanceof Renderable)
    {
      return $this->_makeCubexResponse($response->render());
    }

    if($response instanceof ISafeHtmlProducer)
    {
      return $this->_makeCubexResponse($response->produceSafeHTML()->getContent());
    }

    try
    {
      Strings::stringable($response);
      return $this->_makeCubexResponse($response);
    }
    catch(\InvalidArgumentException $e)
    {
    }
    throw new \RuntimeException(self::ERROR_INVALID_ROUTE_RESPONSE, 500);
  }

  protected function _makeCubexResponse($content)
  {
    $result = new CubexResponse($content);
    if($this->_callStartTime)
    {
      $result->setCallStartTime($this->_callStartTime);
    }
    return $result;
  }

  /**
   * Prepare a response before handling the result
   *
   * @param Context $c
   * @param         $obj
   *
   * @return mixed
   */
  protected function _prepareResponse(Context $c, $obj)
  {
    if($obj instanceof ContextAware)
    {
      $obj->setContext($c);
    }
    return $obj;
  }

  /**
   * @param Context     $c
   * @param             $response
   * @param string|null $buffer
   *
   * @return Response
   * @throws Exception
   */
  protected function _handleAndPrepareResponse(Context $c, $response, ?string $buffer = null): Response
  {
    return $this->_handleResponse($c, $this->_prepareResponse($c, $response), $buffer);
  }

  /**
   * @return Request
   */
  public function request()
  {
    return $this->getContext()->request();
  }

  /**
   * @return ParameterBag
   */
  public function routeData()
  {
    return $this->getContext()->routeData();
  }

  /**
   * @param string                  $path
   * @param string|callable|Handler $result
   *
   * @return Route
   */
  protected static function _route($path, $result)
  {
    return parent::_route($path, $result);
  }
}
