<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;

class SubTestController extends Controller
{
  protected function _getConditions()
  {
    yield self::route('/sub/router', 'router');
    return 'default';
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
