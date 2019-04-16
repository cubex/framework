<?php
namespace Cubex\Tests\Supporting\Application;

class TestExtendedApplication extends TestApplication
{
  protected function _getConditions()
  {
    yield self::_route('/nothing', null);
    return parent::_getConditions();
  }
}
