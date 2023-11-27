<?php
namespace Cubex\Controller;

use Cubex\Cubex;
use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\Routing\RouteProcessor;
use Cubex\ViewModel\View;
use Cubex\ViewModel\ViewModel;
use Packaged\Context\Context;
use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Packaged\DiContainer\DependencyInjector;
use Packaged\Helpers\Strings;
use Packaged\Http\Request;
use Packaged\Http\Response as CubexResponse;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\Ui\Renderable;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use function is_string;
use function method_exists;
use function microtime;
use function strtolower;
use function ucfirst;

abstract class Controller extends RouteProcessor implements ContextAware, CubexAware
{
  use ContextAwareTrait;
  use CubexAwareTrait;

  protected $_callStartTime;

  // retrieve cubex from the request context
  protected function _cubex(): ?Cubex
  {
    $ctx = $this->hasContext() ? $this->getContext() : null;
    if($ctx instanceof CubexAware)
    {
      return $ctx->getCubex();
    }
    return null;
  }

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

  protected function _prepareHandler(Context $c, $handler)
  {
    $handler = parent::_prepareHandler($c, $handler);
    if(is_string($handler))
    {
      foreach($this->_getRouteMethods($c->request(), $handler) as $attemptMethod)
      {
        if(method_exists($this, $attemptMethod))
        {
          $di = $this->_cubex();
          if($di instanceof DependencyInjector && method_exists($di, 'resolveMethod'))
          {
            return fn(Context $c) => $di->resolveMethod($this, $attemptMethod);
          }

          return [$this, $attemptMethod];
        }
      }
    }
    return $handler;
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

  /**
   * Convert a route response into a valid Response
   *
   * @param Context     $c
   * @param mixed       $result
   * @param null|string $buffer
   *
   * @return mixed|CubexResponse
   */
  protected function _prepareResponse(Context $c, $result, $buffer = null)
  {
    if($result instanceof ContextAware)
    {
      $result->setContext($c);
    }

    if($result instanceof CubexAware)
    {
      $cubex = $this->_cubex();
      if($cubex instanceof Cubex)
      {
        $result->setCubex($cubex);
      }
    }

    if($this->_callStartTime && $result instanceof CubexResponse)
    {
      return $result->setCallStartTime($this->_callStartTime);
    }

    if($result instanceof ViewModel)
    {
      $result = $result->createView($this->_defaultModelView());
      if($result instanceof View)
      {
        $result = $result->render();
      }
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
      //Non stringable $result, return as is
    }

    return $result;
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

  protected function _defaultModelView(): ?string
  {
    return null;
  }
}
