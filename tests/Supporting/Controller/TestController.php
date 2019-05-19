<?php
namespace Cubex\Tests\Supporting\Controller;

use Packaged\Context\Context;
use Cubex\Controller\AuthedController;
use Cubex\Http\FuncHandler;
use Cubex\Tests\Supporting\TestObject;
use Cubex\Tests\Supporting\Ui\TestElement\TestUiElement;
use Cubex\Tests\Supporting\Ui\TestSafeHtmlProducer;
use Exception;
use Packaged\Http\Response;
use Packaged\Http\Responses\AccessDeniedResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TestController extends AuthedController
{
  protected $_authResponse;

  protected function _generateRoutes()
  {
    yield self::_route('/route', 'route');
    yield self::_route('/ui', 'ui');
    yield self::_route('/buffered', 'buffered');
    yield self::_route('/response', 'response');
    yield self::_route('/missing', 'missing');
    yield self::_route('/exception', 'exception');
    yield self::_route('/safe-html', 'safeHtml');
    yield self::_route('/google', RedirectResponse::create('http://www.google.com'));
    yield self::_route('/subs/{dynamic}', SubTestController::class);
    yield self::_route('/sub/call', [SubTestController::class, 'remoteCall']);
    yield self::_route('/sub', SubTestController::class);
    yield self::_route('/badsub', TestObject::class);
    yield self::_route('/default-response', AccessDeniedResponse::class);
    yield self::_route('/handler-route', new FuncHandler(function () { return Response::create('handled route'); }));
    return 'DEFAULT ROUTE';
  }

  public function canProcess(&$response): bool
  {
    if($this->_authResponse !== null)
    {
      if(is_bool($this->_authResponse))
      {
        return $this->_authResponse;
      }
      $response = $this->_authResponse;
      return false;
    }
    return parent::canProcess($response);
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

  /**
   * @param Context $c
   * @param         $handler
   * @param         $response
   *
   * @return bool
   * @throws Exception
   */
  public function processObject(Context $c, $handler, &$response): bool
  {
    return parent::_processHandler($c, $handler, $response);
  }

}
