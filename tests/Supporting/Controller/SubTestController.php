<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;

class SubTestController extends Controller
{
  public function getRoutes()
  {
    return [
      self::route('/sub/router', 'router'),
      self::route('route', 'default'),
    ];
  }

  public function getDefault()
  {
    return 'Default';
  }

  public function getRouter()
  {
    return 'Router';
  }

}
