<?php
namespace Project;

use Cubex\Controller\Controller;
use Project\Layout\Layout;

class DefaultHandler extends Controller
{
  public function getRoutes()
  {
    yield self::route("/hello/world", WorldHandler::class);
    yield self::route("/hello", "page");
    yield self::route("/", "echo");
  }

  public function getPage()
  {
    return new Layout();
  }

  public function getEcho()
  {
    echo "Start Page";
  }
}
