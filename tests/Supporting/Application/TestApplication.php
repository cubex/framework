<?php
namespace Cubex\Tests\Supporting\Application;

use Cubex\Application\Application;
use Cubex\Tests\Supporting\Controller\TestController;
use Packaged\Http\Response;
use Packaged\Routing\Handler\FuncHandler;
use Packaged\Routing\Handler\Handler;

class TestApplication extends Application
{
  protected function _generateRoutes()
  {
    yield self::_route('/not-found', null);
    yield self::_route('/buffered', TestController::class);
    return parent::_generateRoutes();
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
