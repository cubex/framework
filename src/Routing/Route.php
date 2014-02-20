<?php
namespace Cubex\Routing;

class Route implements IRoute
{
  protected $_data;

  public static function create($data)
  {
    $route = new self;
    $route->createFromRaw($data);
    return $route;
  }

  public function createFromRaw($data)
  {
    $this->_data = $data;
  }

  public function getValue()
  {
    return $this->_data;
  }
}
