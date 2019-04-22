<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\SingleRouteController;

class TestSingleRoutedController extends SingleRouteController
{
  public function getRoute() { return "GET REQ"; }

  public function postRoute() { return "POST REQ"; }

  public function ajaxRoute() { return "AJAX REQ"; }

  public function processRoute() { return "PROCESS REQ"; }
}
