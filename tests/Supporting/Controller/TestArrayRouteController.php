<?php
namespace Cubex\Tests\Supporting\Controller;

class TestArrayRouteController extends TestController
{
  protected function _generateRoutes()
  {
    return iterator_to_array(parent::_generateRoutes());
  }
}
