<?php
namespace Cubex\Kernel;

use Cubex\CubexAwareTrait;
use Cubex\CubexException;
use Cubex\Http\Response as CubexResponse;
use Cubex\ICubexAware;
use Cubex\Responses\Error403Response;
use Cubex\Routing\IRoutable;
use Cubex\Routing\IRoute;
use Cubex\Routing\IRouter;
use Cubex\Routing\Route;
use Illuminate\Support\Contracts\RenderableInterface;
use Packaged\Helpers\Arrays;
use Packaged\Helpers\Objects;
use Packaged\Helpers\Path;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class CubexKernel
  implements HttpKernelInterface, ICubexAware, IRoutable
{
  use CubexAwareTrait;

  /**
   * @var int Level to which routing has automatically processed
   */
  protected $_routeLevel = 0;

  /**
   * @var array params sent through by the constructor
   */
  protected $_processParams = [];

  /**
   * @var bool stop re-initialise of the class
   */
  protected $_initialised = false;

  /**
   * @var array method names which are not allowed through attemptMethod
   */
  protected $_restrictedMethods;

  /**
   * @var string path which has already been processed by previous kernels
   */
  protected $_pathProcessed;

  /**
   * @var Request
   */
  protected $_request;

  /**
   * Initialise a class, stopping multiple calls to init()
   */
  final public function init()
  {
    if(!$this->_initialised)
    {
      $this->_init();
      $this->_initialised = true;
    }
  }

  /**
   * Initialise component
   */
  protected function _init()
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
   * The default method to run if no route is found
   *
   * @return null
   */
  public function defaultAction()
  {
    return null;
  }

  /**
   * If no route can be processed, use this route
   *
   * @return null
   */
  public function defaultRoute()
  {
    return 'defaultAction';
  }

  /**
   * Authentication hook, allowing validation before the router is attempted
   *
   * To verify the process can process, return true.
   * To reject the request, and return a custom response, simply return the
   * desired response
   * Any other response will return a standard 403 page to the user
   *
   * @return bool|Response
   */
  public function canProcess()
  {
    return true;
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
    $this->_request = $request;

    $calltime = microtime(true);
    //Initialise the kernel
    $this->init();

    try
    {
      //Check to see if the request is allowed to process
      $authed = $this->canProcess();

      //If can process returns a response, use that to send back to the user
      if($authed instanceof Response)
      {
        return $this->_sanitizeResponse($authed);
      }
      else if($authed !== true)
      {
        return new Error403Response();
      }

      $response = null;
      $route = $this->findRoute($request);

      if($route)
      {
        //Process the route to get the response
        $response = $this->executeRoute(
          $route,
          $request,
          $type,
          $catch
        );
      }

      if(!($response instanceof Response))
      {
        throw CubexException::debugException(
          "The processed route did not yield a valid response",
          404,
          $response
        );
      }
    }
    catch(\Exception $e)
    {
      //shutdown the kernel
      $this->shutdown();
      if($catch)
      {
        return $this->handleException($e);
      }
      else
      {
        throw $e;
      }
    }

    //shutdown the kernel
    $this->shutdown();

    if($response instanceof CubexResponse)
    {
      $response->setCallStartTime($calltime);
    }

    return $response;
  }

  public function findRoute(Request $request)
  {
    $path = ltrim($request->getPathInfo(), '/');
    if($this->_pathProcessed)
    {
      $path = ltrim(
        preg_replace(
          '/^' . preg_quote($this->_pathProcessed, '/') . '/',
          '',
          $path
        ),
        '/'
      );
    }
    $pathParts = null;
    if($path)
    {
      $pathParts = explode('/', $path);
    }

    //Get the default router
    $router = $this->getCubex()->makeWithCubex('\Cubex\Routing\IRouter');
    if(!($router instanceof IRouter))
    {
      throw new \RuntimeException("No IRouter located");
    }
    //Tell the router who its working for
    $router->setSubject($this);
    $route = null;
    try
    {
      $route = $router->process($path);
    }
    catch(\Exception $e)
    {
    }
    if(!$route)
    {
      try
      {
        $route = $router->process(
          Path::buildUnix($this->_pathProcessed, $path)
        );
        $this->_pathProcessed = null;
      }
      catch(\Exception $e)
      {
      }
    }
    if((!$route) && $pathParts)
    {
      $route = $this->autoRoute($request, $pathParts);
    }
    if(!$route)
    {
      $route = Route::create($this->defaultRoute(), $this->_processParams);
    }
    return $route;
  }

  public function autoRoute(Request $request, $pathParts)
  {
    // check auto-routing
    $method = $this->attemptMethod(head($pathParts), $request);
    if($method)
    {
      // remove path upto and including $method
      return Route::create($method, array_slice($pathParts, 1));
    }

    // sub routing
    $classPath = ucfirst(head($pathParts));
    $classPath = ucwords(str_replace(['-', '_'], ' ', $classPath));
    $classPath = str_replace(' ', '', $classPath);

    $namespace = Objects::getNamespace(get_called_class());
    $subRoutes = $this->subRouteTo();

    //No subroutes available
    if($subRoutes !== null && !empty($subRoutes))
    {
      foreach($subRoutes as $subRoute)
      {
        //Half sprintf style, but changed to str_replace for multiple instances
        $attempt = Path::buildWindows(
          $namespace,
          str_replace('%s', $classPath, $subRoute)
        );

        if(class_exists($attempt))
        {
          return Route::create(
            $attempt,
            array_slice($pathParts, 1),
            head($pathParts)
          );
        }
      }
    }
    return null;
  }

  /**
   * Execute a route and attempt to generate a response
   *
   * @param IRoute  $route
   * @param Request $request
   * @param int     $type
   * @param bool    $catch
   *
   * @return CubexResponse|null|Response
   * @throws \Exception
   */
  public function executeRoute(
    IRoute $route, Request $request, $type = self::MASTER_REQUEST,
    $catch = true
  )
  {
    $value = $route->getValue();
    $this->_processParams = $params = $route->getRouteData();

    //If the action has returned a valid response, lets send that back
    if($value instanceof Response)
    {
      return $value;
    }

    //Attempt to see if the route is a callable
    if(is_callable($value))
    {
      $value = $this->_getCallableResult($value, $params);
    }
    else if(is_scalar($value))
    {
      //If is fully qualified class, create class
      $match = '/^(\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/';
      if(stripos($value, '\\') !== false && preg_match($match, $value))
      {
        $class = $value;
        $nsClass = Path::buildWindows(Objects::getNamespace($this), $value);

        try
        {
          if(class_exists($nsClass))
          {
            $value = $this->getCubex()->make($nsClass);
          }
          else
          {
            $value = $this->getCubex()->make($class);
          }
        }
        catch(\Exception $e)
        {
          if(!$catch)
          {
            throw new \RuntimeException(
              "Your route provides an invalid class '$class'"
            );
          }
          else
          {
            return null;
          }
        }
      }
      else
      {
        $call = $this->attemptCallable($value);
        if($call !== null)
        {
          $value = $this->_getCallableResult($call, $params);
        }
        else
        {
          //Attempt to see if the route is a redirect
          $url = $this->attemptUrl($value, $request);
          if($url !== null)
          {
            //Redirect to url
            return new RedirectResponse($url['url'], $url['code']);
          }
          else
          {
            //look for method names
            $method = $this->attemptMethod($value, $request);
            if($method !== null)
            {
              $value = $this->_getCallableResult([$this, $method], $params);
            }
          }
        }
      }
    }

    if($value instanceof CubexKernel)
    {
      $value->_pathProcessed = Path::buildUnix(
        $this->_pathProcessed,
        $route->getMatchedPath()
      );
      $value->_processParams = $params;
    }

    return $this->_processResponse($value, $request, $type, $catch, $params);
  }

  /**
   * Process the response from the route
   *
   * @param         $value
   * @param Request $request
   * @param int     $type
   * @param bool    $catch
   * @param array   $params
   *
   * @return CubexResponse|null|Response
   */
  protected function _processResponse(
    $value, Request $request, $type = self::MASTER_REQUEST, $catch = true,
    $params = []
  )
  {
    $value = $this->_sanitizeResponse($value);

    if(!is_array($params))
    {
      $params = [];
    }

    if($value instanceof Response)
    {
      return $value;
    }

    if($value instanceof CubexKernel)
    {
      $value->_processParams = $params;
    }

    //Support for nested kernels, e.g. project > application > controller
    if($value instanceof HttpKernelInterface)
    {
      return $value->handle($request, $type, $catch);
    }

    if($value instanceof RenderableInterface)
    {
      return new CubexResponse($value);
    }

    if(is_object($value))
    {
      return new CubexResponse($value);
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
   *
   * @throws \Exception
   */
  protected function _getCallableResult($method, $params = null)
  {
    ob_start();
    try
    {
      if($params === null)
      {
        $response = $method();
      }
      else
      {
        $response = call_user_func_array($method, $params);
      }
    }
    catch(\Exception $e)
    {
      ob_get_clean();
      throw $e;
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
    $method = trim($routeValue);
    $callMethod = ucwords(str_replace(['-', '_'], ' ', $method));
    $callMethod = str_replace(' ', '', $callMethod);

    $attempts = [];

    //If a specific underscored method has been requested, we should attempt it
    if(stristr($method, '_'))
    {
      $attempts[] = $method;
    }

    //Handle Ajax Requests
    if($request->isXmlHttpRequest())
    {
      $attempts[] = 'ajax' . $callMethod;
    }

    //Support all HTTP Verbs
    $attempts[] = strtolower($request->getMethod()) . $callMethod;

    $attempts[] = 'render' . $callMethod;
    $attempts[] = 'action' . $callMethod;
    $attempts[] = lcfirst($callMethod);

    foreach($attempts as $attempt)
    {
      if($this->_isMethodRestricted($attempt))
      {
        continue;
      }
      try
      {
        //Ensure the method is public
        $method = new \ReflectionMethod($this, $attempt);
        if($method->isPublic())
        {
          return $attempt;
        }
      }
      catch(\Exception $e)
      {
      }
    }

    return null;
  }

  /**
   * Check to see if a method has been restricted for execution
   *
   * @param $method
   *
   * @return bool
   */
  protected function _isMethodRestricted($method)
  {
    if($this->_restrictedMethods === null)
    {
      $this->_restrictedMethods = [
        'init',
        'shutdown',
        'getRoutes',
        'canProcess',
        'handle',
        'executeRoute',
        'attemptCallable',
        'attemptMethod',
        'attemptUrl',
        'handleResponse',
        'autoRoute',
        'attemptSubClass',
        'getConfigItem',
        'subRouteTo',
        'bindCubex',
        'setCubex',
        'getCubex',
        'isCubexAvailable'
      ];
    }

    //Do not allow methods listed within the restricted methods array
    return in_array($method, $this->_restrictedMethods);
  }

  /**
   * Check the route for a redirect rule
   *
   * @param         $routeValue
   * @param Request $request
   *
   * @return array|null
   */
  public function attemptUrl($routeValue, Request $request = null)
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
        if($request !== null && substr($request->getPathInfo(), -1) == '/')
        {
          //If there is a leading slash, make sure you put them up a directory
          $routeValue = '../' . substr($routeValue, 2);
        }
        else
        {
          //to relative urls e.g. /homepage or about
          $routeValue = substr($routeValue, 2);
        }
      }
      return ['code' => 302, 'url' => $routeValue];
    }
    return null;
  }

  /**
   * Handle the response from a method or callable
   *
   * @param $response
   * @param $capturedOutput
   *
   * @return CubexResponse|null
   */
  public function handleResponse($response, $capturedOutput)
  {
    if(is_object($response))
    {
      return $response;
    }

    if($response !== null)
    {
      return new CubexResponse($response);
    }

    if(!empty($capturedOutput))
    {
      return new CubexResponse($capturedOutput);
    }

    return null;
  }

  /**
   * Generate a response for Exceptions
   *
   * @param \Exception $exception
   *
   * @return CubexResponse|mixed
   */
  public function handleException(\Exception $exception)
  {
    if($exception->getCode() == 404)
    {
      return $this->getCubex()->make('404');
    }
    else
    {
      return $this->getCubex()->exceptionResponse($exception);
    }
  }

  protected function _sanitizeResponse($value)
  {
    if($value instanceof ICubexAware)
    {
      $value->setCubex($this->getCubex());
    }

    return $value;
  }

  /**
   * Retrieve the Cubex request object
   * @return \Cubex\Http\Request
   */
  protected function _getRequest()
  {
    return $this->_request ?: $this->getCubex()->make('request');
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

  /**
   * Return an array of sub classes to attempt to route to, using %s as a
   * replacement for the current route part
   *
   * @return null|string[]
   */
  public function subRouteTo()
  {
    return null;
  }

  /**
   * Set the cubex instance on an object to the current instance
   *
   * @param ICubexAware $instance
   *
   * @return ICubexAware
   * @throws \RuntimeException
   */
  public function bindCubex(ICubexAware $instance)
  {
    $instance->setCubex($this->getCubex());
    return $instance;
  }

  protected function _getRouteData($key = null, $default = null)
  {
    if($key === null)
    {
      return $this->_processParams;
    }

    return Arrays::value($this->_processParams, $key, $default);
  }
}
