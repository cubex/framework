<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
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

  protected $_callStartTime;

  /**
   * Standard route handler, with call time set
   *
   * @param Context $c
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(Context $c): Response
  {
    $this->setContext($c);
    $this->_callStartTime = $this->_callStartTime ?: microtime(true);
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

  /**
   * Convert a route response into a valid Response
   *
   * @param Context $c
   * @param mixed   $result
   * @param null    $buffer
   *
   * @return mixed|CubexResponse
   */
  protected function _prepareResponse(Context $c, $result, $buffer = null)
  {
    if($result instanceof ContextAware)
    {
      $result->setContext($c);
    }

    if($this->_callStartTime && $result instanceof CubexResponse)
    {
      return $result->setCallStartTime($this->_callStartTime);
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
}
