<?php
namespace Cubex\Kernel;

use Cubex\CubexAwareTrait;
use Cubex\CubexException;
use Cubex\ICubexAware;
use Cubex\Routing\IRoutable;
use Cubex\Routing\IRoute;
use Cubex\Routing\IRouter;
use Cubex\Routing\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class CubexKernel
  implements HttpKernelInterface, ICubexAware, IRoutable
{
  use CubexAwareTrait;

  /**
   * Initialise component
   */
  public function init()
  {
  }

  /**
   * Shutdown component
   */
  public function shutdown()
  {
  }

  /**
   * Get available routes for this object
   *
   * @return array|null
   */
  public function getRoutes()
  {
    return null;
  }

  /**
   * If no route can be processed, run this action
   *
   * @return null
   */
  public function defaultAction()
  {
    return null;
  }

  /**
   * Handles a Request to convert it to a Response.
   *
   * When $catch is true, the implementation must catch all exceptions
   * and do its best to convert them to a Response instance.
   *
   * @param Request $request  A Request instance
   * @param integer $type     The type of the request
   *                          (one of HttpKernelInterface::MASTER_REQUEST
   *                          or HttpKernelInterface::SUB_REQUEST)
   * @param Boolean $catch    Whether to catch exceptions or not
   *
   * @return Response A Response instance
   *
   * @throws \Exception When an Exception occurs during processing
   *
   * @api
   */
  public function handle(
    Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    //Imitialise the kernel
    $this->init();

    try
    {
      //Get the default router
      $router = $this->getCubex()->makeWithCubex('\Cubex\Routing\IRouter');
      if(!($router instanceof IRouter))
      {
        throw new \RuntimeException("No IRouter located");
      }
      //Tell the router who its working for
      $router->setSubject($this);
      $response = null;

      try
      {
        //Process the route to get the response
        $response = $this->executeRoute(
          $router->process($request->getPathInfo()),
          $request,
          $type,
          $catch
        );
      }
      catch(\Exception $e)
      {
        try
        {
          $response = $this->autoRoute($request);
        }
        catch(\Exception $e)
        {
        }
      }

      if(!($response instanceof Response))
      {
        $response = $this->executeRoute(
          Route::create('defaultAction'),
          $request,
          $type,
          $catch
        );
      }

      if(!($response instanceof Response))
      {
        throw CubexException::debugException(
          "The processed route did not yield a valid response",
          500,
          $response
        );
      }
    }
    catch(\Exception $e)
    {
      if($catch)
      {
        //shutdown the kernel
        $this->shutdown();

        return $this->getCubex()->make('404');
      }
      else
      {
        //shutdown the kernel
        $this->shutdown();

        throw $e;
      }
    }

    //shutdown the kernel
    $this->shutdown();

    return $response;
  }

  public function executeRoute(
    IRoute $route, Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    $value = $route->getValue();

    //If the action has returned a valid response, lets send that back
    if($value instanceof Response)
    {
      return $value;
    }

    if(is_scalar($value))
    {
      //If is fully qualified class, create class
      $match = '/^(\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/';
      if(stripos($value, '\\') !== false && preg_match($match, $value))
      {
        $value = $this->getCubex()->make($value);
      }
      else
      {
        //Attempt to see if the route is a callable
        $call = $this->attemptCallable($value);
        if($call !== null)
        {
          return $call();
        }

        //Attempt to see if the route is a redirect
        $url = $this->attemptUrl($value);
        if($url !== null)
        {
          //Redirect to url
          return new RedirectResponse($url['url'], $url['code']);
        }

        //look for method names
        $method = $this->attemptMethod($value, $request);
        if($method !== null)
        {
          return $this->_getMethodResult($method);
        }
      }
    }

    if($value instanceof ICubexAware)
    {
      $value->setCubex($this->getCubex());
    }

    if($value instanceof Response)
    {
      return $value;
    }

    //Support for nested kernels, e.g. project > application > controller
    if($value instanceof HttpKernelInterface)
    {
      return $value->handle($request, $type, $catch);
    }

    if(is_callable($value))
    {
      return $value();
    }

    if(is_object($value) && method_exists($value, '__toString'))
    {
      return (string)$value;
    }

    return null;
  }

  /**
   * Execute a method, and convert the response or output into a response
   *
   * @param            $method
   * @param null|array $params Parameters to send into the method
   *
   * @return \Cubex\Http\Response|null
   */
  protected function _getMethodResult($method, $params = null)
  {
    ob_start();
    if($params === null)
    {
      $response = $this->$method();
    }
    else
    {
      $response = call_user_func_array([$this, $method], $params);
    }
    return $this->handleResponse($response, ob_get_clean());
  }

  /**
   * Attempt to convert a string to a callable
   *
   * @param $routeValue
   *
   * @return callable|null
   */
  public function attemptCallable($routeValue)
  {
    if(stristr($routeValue, ','))
    {
      $routeValue = explode(',', $routeValue);
    }
    else if(stristr($routeValue, '@'))
    {
      $routeValue = explode('@', $routeValue);
    }

    if(is_callable($routeValue))
    {
      return $routeValue;
    }

    return null;
  }

  /**
   * Search the current class for a matching method
   *
   * @param         $routeValue
   * @param Request $request
   *
   * @return null
   */
  public function attemptMethod($routeValue, Request $request)
  {
    $method     = trim($routeValue);
    $callMethod = \ucfirst($method);

    $attempts = [];
    //Handle Ajax Requests
    if($request->isXmlHttpRequest())
    {
      $attempts[] = 'ajax' . $callMethod;
    }

    if($request->isMethod('POST'))
    {
      $attempts[] = 'post' . $callMethod;
    }

    $attempts[] = 'render' . $callMethod;
    $attempts[] = 'action' . $callMethod;
    $attempts[] = $method;

    foreach($attempts as $attempt)
    {
      if(method_exists($this, $attempt))
      {
        return $attempt;
      }
    }
    return null;
  }

  /**
   * Check the route for a redirect rule
   *
   * @param $routeValue
   *
   * @return array|null
   */
  public function attemptUrl($routeValue)
  {
    //Match full urls, status code and urls, and relative urls.
    if(preg_match('/^(.*tps?:\/\/|\/|#@|@[0-9]{3}\!)/', $routeValue))
    {
      //If specified, use the http code provided e.g. @301!http://google.com
      if(substr($routeValue, 0, 1) == '@')
      {
        list($code, $url) = sscanf($routeValue, "@%d!%s");
        return ['code' => $code, 'url' => $url];
      }
      else if(substr($routeValue, 0, 2) == '#@')
      {
        //Starting a url with #@ implies a redirect.  Useful for redirecting
        //to relative urls e.g. /homepage or about
        $routeValue = substr($routeValue, 2);
      }
      return ['code' => 302, 'url' => $routeValue];
    }
    return null;
  }

  public function handleResponse($response, $capturedOutput)
  {
    if($response instanceof Response)
    {
      return $response;
    }

    if($response !== null && !empty($response))
    {
      return new \Cubex\Http\Response($response);
    }

    if($capturedOutput !== null && !empty($capturedOutput))
    {
      return new \Cubex\Http\Response($capturedOutput);
    }

    return null;
  }

  /**
   * Auto build the result based on the URL
   *
   * @param Request $request
   *
   * @return null|Response
   */
  public function autoRoute(Request $request)
  {
    $params      = null;
    $paramString = trim($request->getPathInfo(), '/');
    if(!$paramString)
    {
      return null;
    }

    $params = explode('/', $paramString);
    $path   = array_shift($params);

    if(empty($params))
    {
      $params = null;
    }

    //Does the method exist?
    $method = $this->attemptMethod($path, $request);
    if($method !== null)
    {
      return $this->_getMethodResult($method, $params);
    }

    //Nothing was found to automatically make things better
    return null;
  }

  /**
   * Retrieve a cubex configuration item
   *
   * @param      $section
   * @param      $item
   * @param null $default
   *
   * @return mixed
   */
  public function getConfigItem($section, $item, $default = null)
  {
    return $this->getCubex()->getConfiguration()->getItem(
      $section,
      $item,
      $default
    );
  }
}
