<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;

class SubTestController extends Controller
{
  public function getRoutes()
  {
    return [
      self::route('/sub/route', 'default'),
    ];
  }

  public function getDefault()
  {
    return 'Default';
  }

}
