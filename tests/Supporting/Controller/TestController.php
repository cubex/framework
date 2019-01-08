<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;
use Cubex\Http\FuncHandler;
use Cubex\Tests\Supporting\Container\TestObject;
use Cubex\Tests\Supporting\Ui\TestElement\TestUiElement;
use Exception;
use Packaged\Http\Response;
use Packaged\Http\Responses\AccessDeniedResponse;

class TestController extends Controller
{
  protected $_authResponse;

  public function getRoutes()
  {
    return [
      self::route('/route', 'route'),
      self::route('/ui', 'ui'),
      self::route('/buffered', 'buffered'),
      self::route('/response', 'response'),
      self::route('/missing', 'missing'),
      self::route('/exception', 'exception'),
      self::route('/sub', SubTestController::class),
      self::route('/badsub', TestObject::class),
      self::route('/default-response', AccessDeniedResponse::class),
      self::route(
        '/handler-route',
        new FuncHandler(
          function () {
            return Response::create('handled route');
          }
        )
      ),
    ];
  }

  public function canProcess()
  {
    return $this->_authResponse ?? parent::canProcess();
  }

  public function setAuthResponse($authResponse)
  {
    $this->_authResponse = $authResponse;
    return $this;
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

  public function getException()
  {
    throw new Exception("Broken");
  }
}