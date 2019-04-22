<?php
namespace Cubex\Tests\Supporting\Application;

class TestExtendedApplication extends TestApplication
{
  protected function _generateRoutes()
  {
    yield self::_route('/nothing', null);
    return parent::_generateRoutes();
  }
}
