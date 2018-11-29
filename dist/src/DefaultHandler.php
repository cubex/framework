<?php
namespace Project;

use Cubex\Controller\Controller;

class DefaultHandler extends Controller
{
  protected function defaultRoute()
  {
    //return 'page';
    return WorldHandler::class;
  }

  public function getPage()
  {
    return 'Hello Page';
  }
}
