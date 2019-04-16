<?php
namespace Cubex\Tests\Supporting\Application;

use Cubex\Application\Application;

class TestApplication extends Application
{
  protected function _getConditions()
  {
    return null;
  }

  public function clearCubex()
  {
    return $this->_clearCubex();
  }
}
