<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\SingleRouteController;

class TestSingleRoutedController extends SingleRouteController
{
  public function get() { return "GET REQ"; }

  public function post() { return "POST REQ"; }

  public function ajax() { return "AJAX REQ"; }

  public function process() { return "PROCESS REQ"; }
}
