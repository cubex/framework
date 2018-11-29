<?php
namespace Project;

use Cubex\Controller\Controller;
use Cubex\Controller\Route;

class DefaultHandler extends Controller
{
  public function getRoutes()
  {
    return [
      Route::i("/hello/world", WorldHandler::class),
      Route::i("/hello", "page"),
    ];
  }

  public function getPage()
  {
    return 'Hello Page';
  }
}
