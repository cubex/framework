<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Exception;

class Controller implements Handler, ContextAware
{
  use ContextAwareTrait;

  public function canProcess()
  {
    return true;
  }

  public function getRoutes()
  {
    return [];
  }

  protected function defaultRoute()
  {
    return 'page';
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

    //Loop over routes
    $result = $this->defaultRoute();

    if($result && is_string($result))
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
          return $obj->handle($c, $w, $r);
        }
      }

      $method = $this->_getMethod($r, $result);
      if($method !== null)
      {
        $methodResponse = null;
        ob_start();
        try
        {
          $methodResponse = $this->$method();
        }
        catch(\Exception $e)
        {
          ob_get_clean();
          throw $e;
        }

        $w->setContent($this->_convertResponse($methodResponse, ob_get_clean()));
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