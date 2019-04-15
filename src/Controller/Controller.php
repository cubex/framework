<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Routing\ConditionSelector;
use Exception;
use Packaged\Helpers\Strings;
use Packaged\Http\Request;
use Packaged\Http\Response as CubexResponse;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller extends ConditionSelector implements Handler, ContextAware
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

    $result = $this->_getHandler($c);

    $this->_callStartTime = microtime(true);

    if($result instanceof Handler)
    {
      return $result->handle($c);
    }

    if(is_callable($result))
    {
      return $this->_executeCallable($c, $result);
    }

    if($result !== null && is_string($result))
    {
      if(strstr($result, '\\') && class_exists($result))
      {
        return $this->_executeClass($c, $result);
      }

      $callable = is_callable($result) ? $result : $this->_getMethod($this->getContext()->request(), $result);
      if(is_callable($callable))
      {
        return $this->_executeCallable($c, $callable);
      }
    }

    return $this->_processRoute($result);
  }

  /**
   * If all else fails, push the route result through this method
   *
   * @param string $routeResult Result of the located route
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

  private function _getMethod(Request $r, $method)
  {
    $method = ucfirst($method);
    $prefixes = [];
    if($r->isXmlHttpRequest())
    {
      $prefixes[] = 'ajax';
      $prefixes[] = 'ajax' . ucfirst(strtolower($r->getMethod()));
    }

    $prefixes[] = strtolower($r->getMethod());
    $prefixes[] = 'process'; //support for all methods

    foreach($prefixes as $prefix)
    {
      if(method_exists($this, $prefix . $method))
      {
        return [$this, $prefix . $method];
      }
    }
    return null;
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
    return $this->_handleAndPrepareResponse($c, $obj);
  }

  protected function _handleResponse(Context $c, $response, ?string $buffer = null): Response
  {
    if($response === null)
    {
      $response = $buffer;
    }

    if($response instanceof Handler)
    {
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
}
