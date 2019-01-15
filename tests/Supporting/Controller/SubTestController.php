<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;

class SubTestController extends Controller
{
  public function getRoutes()
  {
    yield self::route('/sub/router', 'router');
    yield self::route('route', 'default');
  }

  public function getDefault()
  {
    return 'Default';
  }

  public function getRouter()
  {
    return 'Router';
  }

  public static function remoteCall()
  {
    return 'Remote';
  }

}
