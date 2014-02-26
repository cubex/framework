<?php

class RouteTest extends PHPUnit_Framework_TestCase
{
  public function testRouteValue()
  {
    $route = new \Cubex\Routing\Route();
    $route->createFromRaw("test");
    $this->assertEquals("test", $route->getValue());
    $this->assertEquals("ab", \Cubex\Routing\Route::create("ab")->getValue());
  }

  public function testRouteData()
  {
    $data  = ["name" => "john", "surname" => "smith"];
    $route = new \Cubex\Routing\Route();
    $route->setRouteData($data);
    $this->assertEquals($data, $route->getRouteData());
    $route->setRouteData("invalid");
    $this->assertNull($route->getRouteData());
    $staticRoute = \Cubex\Routing\Route::create("a", $data);
    $this->assertEquals($data, $staticRoute->getRouteData());
  }
}
