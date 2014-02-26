<?php
namespace Cubex\Routing;

class Route implements IRoute
{
  protected $_data;
  protected $_params;

  /**
   * Quick and dirty route creation
   *
   * @param      $data
   * @param null $params
   *
   * @return Route
   */
  public static function create($data, $params = null)
  {
    $route = new self;
    $route->createFromRaw($data);
    if($params !== null)
    {
      $route->setRouteData($params);
    }
    return $route;
  }

  /**
   * Create the route from the raw data returned in the router
   *
   * @param $data
   */
  public function createFromRaw($data)
  {
    $this->_data = $data;
  }

  /**
   * Set the URL data
   *
   * @param $params
   */
  public function setRouteData($params)
  {
    if(is_array($params))
    {
      $this->_params = $params;
    }
    else
    {
      $this->_params = null;
    }
  }

  /**
   * The route result e.g. the method or action returned in the router
   *
   * @return mixed
   */
  public function getValue()
  {
    return $this->_data;
  }

  /**
   * Retrieve route data e.g. URL Parameters
   *
   * @return array|null
   */
  public function getRouteData()
  {
    return $this->_params;
  }
}
