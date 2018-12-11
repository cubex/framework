<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;
use Cubex\Tests\Supporting\Ui\TestElement\TestUiElement;
use Packaged\Http\Response;

class TestController extends Controller
{
  public function getRoutes()
  {
    return [
      self::route('/route', 'route'),
      self::route('/ui', 'ui'),
      self::route('/buffered', 'buffered'),
      self::route('/response', 'response'),
      self::route('/missing', 'missing'),
    ];
  }

  public function postRoute()
  {
    return 'POST ROUTE';
  }

  public function getRoute()
  {
    return 'GET ROUTE';
  }

  public function ajaxGetRoute()
  {
    return 'AJAX GET ROUTE';
  }

  public function getUi()
  {
    $el = new TestUiElement();
    $this->_bindContext($el);
    $el->setContent("Testing UI Route");
    return $el;
  }

  public function getBuffered()
  {
    echo 'BUFFER';
  }

  public function getResponse()
  {
    return Response::create('Fixed Response');
  }
}
