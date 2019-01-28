<?php
namespace Project;

use Cubex\Controller\Controller;

class WorldHandler extends Controller
{
  public function getRoutes()
  {
    yield self::route("", "page");
  }

  public function getPage()
  {
    return 'Hello World';
  }
}
