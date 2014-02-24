<?php
namespace Cubex\Routing;

use Cubex\CubexAwareTrait;

class Router implements IRouter
{
  use CubexAwareTrait;

  protected $_subject;

  /**
   * Set the object you wish to handle routing for
   *
   * @param IRoutable $subject
   *
   * @return $this
   */
  public function setSubject(IRoutable $subject)
  {
    $this->_subject = $subject;
    return $this;
  }

  /**
   * Process the url against the subjects routes
   *
   * @param $url
   *
   * @return IRoute
   * @throws \RuntimeException When the subject has not been set
   * @throws \Exception When no route can be found
   */
  public function process($url)
  {
    if($this->_subject === null || !($this->_subject instanceof IRoutable))
    {
      throw new \RuntimeException("No routable subject has been defined");
    }

    $route = $this->_processRoutes($url, $this->_subject->getRoutes());
    if($route instanceof IRoute)
    {
      return $route;
    }

    throw new RouteNotFoundException("Unable to locate a suitable route");
  }

  protected function _processRoutes($url, $routes)
  {
    if(!is_array($routes))
    {
      return null;
    }

    $url = ltrim($url, '/');

    foreach($routes as $pattern => $route)
    {
      if($this->matchPattern($url, $pattern))
      {
        if(is_array($route))
        {
          $subUrl   = $this->stripUrlWithPattern($url, $pattern);
          $subMatch = $this->_processRoutes($subUrl, $route);
          if($subMatch !== null)
          {
            return $this->createRoute($subMatch);
          }
        }
        return $this->createRoute($route);
      }
    }

    return null;
  }

  public function stripUrlWithPattern($url, $pattern)
  {
    return substr($url, strlen($pattern));
  }

  public function matchPattern($url, $pattern)
  {
    //We need a pattern to match, null or empty are too vague
    if($pattern === null || empty($pattern))
    {
      return false;
    }

    return starts_with($url, $pattern, false);
  }

  /**
   * Create a route from the raw data
   *
   * @param $data
   *
   * @return Route
   */
  public function createRoute($data)
  {
    if($data instanceof IRoute)
    {
      return $data;
    }

    return Route::create($data);
  }
}
