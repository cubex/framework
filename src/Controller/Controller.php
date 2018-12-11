<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Routing\Constraint;
use Cubex\Routing\Route;
use Packaged\Http\Request;
use Packaged\Http\Response as CubexResponse;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller implements Handler, ContextAware
{
  use ContextAwareTrait;

  const ERROR_ACCESS_DENIED = "you are not permitted to access this url";
  const ERROR_NO_ROUTE = "unable to handle your request";
  const ERROR_INVALID_ROUTE_CLASS = "unable to process your request";

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
    return Route::with(Constraint::path($path))->setHandler($result);
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
    ob_start();
    $this->setContext($c);

    //Verify the request can be processed
    $authResponse = $this->canProcess();
    if($authResponse instanceof Response)
    {
      ob_end_clean();
      return $authResponse;
    }
    else if($authResponse !== true)
    {
      ob_end_clean();
      throw new \Exception(self::ERROR_ACCESS_DENIED, 403);
    }

    $result = $this->_getRoute();

    if($result !== null && is_string($result))
    {
      if(strstr($result, '\\') && class_exists($result))
      {
        return $this->_executeClass($c, $result);
      }
      ob_end_clean();

      $callable = is_callable($result) ? $result : $this->_getMethod($this->getContext()->getRequest(), $result);
      if(is_callable($callable))
      {
        return $this->_executeCallable($callable);
      }
    }

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

  protected function _handleResponse($response, ?string $buffer): Response
  {
    if($response === null)
    {
      $response = $buffer;
    }
    else if($response instanceof ContextAware)
    {
      $response->setContext($this->getContext());
    }

    if($response instanceof Response)
    {
      return $response;
    }

    return new CubexResponse($response instanceof Renderable ? $response->render() : $response);
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
    foreach($this->getRoutes() as $route)
    {
      if($route instanceof Route && $route->match($this->getContext()->getRequest()))
      {
        return $route->getHandler();
      }
    }
    return null;
  }

  /**
   * @param $callable
   *
   * @return Response
   * @throws \Throwable
   */
  protected function _executeCallable($callable): Response
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

    return $this->_handleResponse($callableResponse, ob_get_clean());
  }

  /**
   * @param Context $c
   * @param         $result
   *
   * @return Response
   */
  protected function _executeClass(Context $c, $result): Response
  {
    $obj = new $result();
    if($obj instanceof ContextAware)
    {
      $obj->setContext($c);
    }

    if($obj instanceof Handler)
    {
      ob_end_clean();
      return $obj->handle($c);
    }

    ob_end_clean();
    throw new \RuntimeException(self::ERROR_INVALID_ROUTE_CLASS, 500);
  }
}
