<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Routing\Constraint;
use Cubex\Routing\Route;
use Packaged\Http\Request;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller implements Handler, ContextAware
{
  use ContextAwareTrait;

  public function canProcess()
  {
    return true;
  }

  /**
   * @return Route[]
   */
  abstract public function getRoutes();

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
      return $authResponse;
    }
    else if($authResponse !== true)
    {
      throw new \Exception("unable to process your request", 403);
    }

    $result = null;
    foreach($this->getRoutes() as $route)
    {
      if($route instanceof Route && $route->match($this->getContext()->getRequest()))
      {
        $result = $route->getHandler();
        break;
      }
    }

    if($result !== null && is_string($result))
    {
      if(strstr($result, '\\') && class_exists($result))
      {
        $obj = new $result();
        if($obj instanceof ContextAware)
        {
          $obj->setContext($c);
        }

        if($obj instanceof Handler)
        {
          return $obj->handle($c);
        }

        throw new \RuntimeException("unable to handle your request", 500);
      }
      ob_end_clean();

      $callable = is_callable($result) ? $result : $this->_getMethod($this->getContext()->getRequest(), $result);
      if(is_callable($callable))
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
    }

    throw new \RuntimeException("unable to handle your request", 404);
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

    return new Response($response instanceof Renderable ? $response->render() : $response);
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
}
