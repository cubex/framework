<?php
namespace CubexTest\Cubex\Routing;

use Cubex\Routing\Route;

class RouteTest extends \PHPUnit_Framework_TestCase
{
  public function testRouteValue()
  {
    $route = new Route();
    $route->createFromRaw("test");
    $this->assertEquals("test", $route->getValue());
    $this->assertEquals("ab", Route::create("ab")->getValue());
  }

  public function testRouteData()
  {
    $data = ["name" => "john", "surname" => "smith"];
    $route = new Route();
    $route->setRouteData($data);
    $this->assertEquals($data, $route->getRouteData());
    $route->setRouteData("invalid");
    $this->assertNull($route->getRouteData());
    $staticRoute = Route::create("a", $data);
    $this->assertEquals($data, $staticRoute->getRouteData());
  }
}
