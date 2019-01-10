<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\SingleRouteController;

class TestSingleRoutedController extends SingleRouteController
{
  public function getReq() { return "GET REQ"; }

  public function postReq() { return "POST REQ"; }

  public function ajaxReq() { return "AJAX REQ"; }

  public function processReq() { return "PROCESS REQ"; }
}
