<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Cubex\Routing\Constraint;
use Cubex\Routing\Route;
use Exception;

abstract class Controller implements Handler, ContextAware
{
  use ContextAwareTrait;
  private $_response;
  private $_request;

  /**
   * @return Response
   */
  public function getResponse(): ?Response
  {
    return $this->_response;
  }

  /**
   * @param Response $response
   *
   * @return Controller
   */
  public function setResponse(Response $response)
  {
    $this->_response = $response;
    return $this;
  }

  /**
   * @return Request
   */
  public function getRequest(): ?Request
  {
    return $this->_request;
  }

  /**
   * @param Request $request
   *
   * @return Controller
   */
  public function setRequest(Request $request)
  {
    $this->_request = $request;
    return $this;
  }

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
   * @param Context  $c
   * @param Response $w
   * @param Request  $r
   *
   * @return bool
   * @throws Exception
   */
  public function handle(Context $c, Response $w, Request $r)
  {
    $this->setContext($c);
    $this->setRequest($r);
    $this->setResponse($w);

    //Verify the request can be processed
    $authResponse = $this->canProcess();
    if($authResponse instanceof Response)
    {
      $w->setStatusCode($authResponse->getStatusCode());
      $w->setContent($authResponse->getContent());
      return true;
    }
    else if($authResponse !== true)
    {
      throw new \Exception("unable to process your request", 403);
    }

    $result = null;
    foreach($this->getRoutes() as $route)
    {
      if($route instanceof Route && $route->match($r))
      {
        $result = $route->getHandler();
        break;
      }
    }

    if($result !== null && is_string($result))
    {
      $callable = null;
      if(strstr($result, '\\') && class_exists($result))
      {
        $obj = new $result();
        if($obj instanceof ContextAware)
        {
          $obj->setContext($c);
        }

        if($obj instanceof Controller)
        {
          $obj->setRequest($r);
          $obj->setResponse($w);
        }

        if($obj instanceof Handler)
        {
          return $obj->handle($c, $w, $r);
        }

        throw new \RuntimeException("unable to handle your request", 500);
      }

      $callable = is_callable($result) ? $result : $this->_getMethod($r, $result);
      if(is_callable($callable))
      {
        ob_start();
        try
        {
          $callableResponse = $callable();
        }
        catch(\Exception $e)
        {
          ob_get_clean();
          throw $e;
        }

        $w->setContent($this->_convertResponse($callableResponse, ob_get_clean()));
        return true;
      }
    }

    throw new \RuntimeException("unable to handle your request", 404);
  }

  protected function _convertResponse($response, $buffer)
  {
    if($response === null)
    {
      $response = $buffer;
    }
    return $response;
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
        return $prefix . $method;
      }
    }
    return null;
  }
}
