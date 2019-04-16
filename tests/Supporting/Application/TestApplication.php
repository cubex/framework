<?php
namespace Cubex\Tests\Supporting\Application;

use Cubex\Application\Application;
use Cubex\Http\FuncHandler;
use Cubex\Http\Handler;
use Packaged\Http\Response;

class TestApplication extends Application
{
  protected function _getConditions()
  {
    yield self::_route('/not-found', null);
    return parent::_getConditions();
  }

  protected function _defaultHandler(): Handler
  {
    return new FuncHandler(function () { return Response::create('App Default'); });
  }

  public function clearCubex()
  {
    return $this->_clearCubex();
  }
}
