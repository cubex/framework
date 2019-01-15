<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Routing\HttpConstraint;
use Cubex\Routing\Route;
use Exception;
use Generator;
use Packaged\Helpers\Strings;
use Packaged\Http\Request;
use Packaged\Http\Response as CubexResponse;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\Response;
use Traversable;

abstract class Controller implements Handler, ContextAware
{
  use ContextAwareTrait;

  const ERROR_ACCESS_DENIED = "you are not permitted to access this url";
  const ERROR_NO_ROUTE = "unable to handle your request";
  const ERROR_INVALID_ROUTE_RESPONSE = "unable to process your request";

  protected $_callStartTime;

  /**
   * @return Route[]
   */
  abstract public function getRoutes();

  /**
   * @return bool|Response
   */
  public function canProcess()
  {
    return true;
  }

  /**
   * @param                         $path
   * @param string|callable|Handler $result
   *
   * @return Route
   */
  public static function route($path, $result)
  {
    return Route::with(HttpConstraint::path($path))->setHandler($result);
  }

  /**
   * @return Request
   */
  public function getRequest()
  {
    return $this->getContext()->getRequest();
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

    $result = $this->_getRoute();

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

      $callable = is_callable($result) ? $result : $this->_getMethod($this->getContext()->getRequest(), $result);
      if(is_callable($callable))
      {
        return $this->_executeCallable($c, $callable);
      }
    }

    return $this->_processRoute($result);
  }

  /**
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
   * @return callable|Handler|null|string
   */
  protected function _getRoute()
  {
    $routes = $this->getRoutes();
    if($routes instanceof Traversable || is_array($routes))
    {
      foreach($routes as $route)
      {
        if($route instanceof Route && $route->match($this->getContext()))
        {
          return $route->getHandler();
        }
      }

      if($routes instanceof Generator)
      {
        return $routes->getReturn();
      }
    }
    else if($routes instanceof Handler || is_callable($routes) || is_string($routes))
    {
      return $routes;
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
      $result = new CubexResponse($response->render());
      if($this->_callStartTime)
      {
        $result->setCallStartTime($this->_callStartTime);
      }
      return $result;
    }

    try
    {
      Strings::stringable($response);
      return new CubexResponse($response);
    }
    catch(\InvalidArgumentException $e)
    {
    }
    throw new \RuntimeException(self::ERROR_INVALID_ROUTE_RESPONSE, 500);
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
}
