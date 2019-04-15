<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;
use Cubex\Http\FuncHandler;
use Cubex\Tests\Supporting\Container\TestObject;
use Cubex\Tests\Supporting\Ui\TestElement\TestUiElement;
use Cubex\Tests\Supporting\Ui\TestSafeHtmlProducer;
use Exception;
use Packaged\Http\Response;
use Packaged\Http\Responses\AccessDeniedResponse;

class TestController extends Controller
{
  protected $_authResponse;

  protected function _getConditions()
  {
    yield self::route('/route', 'route');
    yield self::route('/ui', 'ui');
    yield self::route('/buffered', 'buffered');
    yield self::route('/response', 'response');
    yield self::route('/missing', 'missing');
    yield self::route('/exception', 'exception');
    yield self::route('/safe-html', 'safeHtml');
    yield self::route('/subs/{dynamic}', SubTestController::class);
    yield self::route('/sub/call', [SubTestController::class, 'remoteCall']);
    yield self::route('/sub', SubTestController::class);
    yield self::route('/badsub', TestObject::class);
    yield self::route('/default-response', AccessDeniedResponse::class);
    yield self::route('/handler-route', new FuncHandler(function () { return Response::create('handled route'); }));
    return 'DEFAULT ROUTE';
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

  public function getSafeHtml()
  {
    return new TestSafeHtmlProducer();
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

  /**
   * @throws Exception
   */
  public function getException()
  {
    throw new Exception("Broken");
  }
}
