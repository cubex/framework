<?php
namespace Project;

use Cubex\Controller\Controller;
use Project\Layout\Layout;

class DefaultHandler extends Controller
{
  public function getRoutes()
  {
    return [
      self::route("/hello/world", WorldHandler::class),
      self::route("/hello", "page"),
    ];
  }

  public function getPage()
  {
    return new Layout();
  }
}
