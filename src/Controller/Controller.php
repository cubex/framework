<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Routing\Route;
use Cubex\Routing\RouteProcessor;
use Packaged\Helpers\Strings;
use Packaged\Http\Request;
use Packaged\Http\Response as CubexResponse;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller extends RouteProcessor implements ContextAware
{
  use ContextAwareTrait;

  const ERROR_ACCESS_DENIED = "you are not permitted to access this url";

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

    $this->_callStartTime = microtime(true);
    return parent::handle($c);
  }

  /**
   * @param Context $c
   * @param string  $handler
   *
   * @param         $response
   *
   * @return bool
   * @throws \Throwable
   */
  protected function _processStringHandler(Context $c, string $handler, &$response): bool
  {
    $processed = parent::_processStringHandler($c, $handler, $response);
    if(!$processed)
    {
      foreach($this->_getRouteMethods($c->request(), $handler) as $attemptMethod)
      {
        if(method_exists($this, $attemptMethod))
        {
          $this->_processMixed($c, $this->$attemptMethod(), $response);
          return true;
        }
      }
    }
    return $processed;
  }

  /**
   * Yield methods to attempt
   *
   * @param Request $r
   * @param string  $method
   *
   * @return \Generator|null
   */
  protected function _getRouteMethods(Request $r, string $method)
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

  protected function _makeCubexResponse($content)
  {
    $result = new CubexResponse($content);
    if($this->_callStartTime)
    {
      $result->setCallStartTime($this->_callStartTime);
    }
    return $result;
  }

  protected function _prepareResponse(Context $c, $result, $buffer = null)
  {
    if($result instanceof ContextAware)
    {
      $result->setContext($c);
    }

    if($result instanceof Response)
    {
      return $result;
    }

    if($result instanceof Renderable)
    {
      return $this->_makeCubexResponse($result->render());
    }

    if($result instanceof ISafeHtmlProducer)
    {
      return $this->_makeCubexResponse($result->produceSafeHTML()->getContent());
    }

    $result = parent::_prepareResponse($c, $result, $buffer);

    try
    {
      Strings::stringable($result);
      return $this->_makeCubexResponse($result);
    }
    catch(\InvalidArgumentException $e)
    {
    }

    return $result;
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
