<?php

class RouteTest extends PHPUnit_Framework_TestCase
{
  public function testRouteData()
  {
    $route = new \Cubex\Routing\Route();
    $route->createFromRaw("test");
    $this->assertEquals("test", $route->getValue());
    $this->assertEquals("ab", \Cubex\Routing\Route::create("ab")->getValue());
  }
}
