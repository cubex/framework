<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Http\FuncHandler;
use Cubex\Tests\Supporting\Container\TestObject;
use Packaged\Http\Response;
use Packaged\Http\Responses\AccessDeniedResponse;

class TestArrayRouteController extends TestController
{
  protected function _getConditions()
  {
    return [
      self::_route('/route', 'route'),
      self::_route('/ui', 'ui'),
      self::_route('/buffered', 'buffered'),
      self::_route('/response', 'response'),
      self::_route('/missing', 'missing'),
      self::_route('/exception', 'exception'),
      self::_route('/safe-html', 'safeHtml'),
      self::_route('/subs/{dynamic}', SubTestController::class),
      self::_route('/sub/call', [SubTestController::class, 'remoteCall']),
      self::_route('/sub', SubTestController::class),
      self::_route('/badsub', TestObject::class),
      self::_route('/default-response', AccessDeniedResponse::class),
      self::_route('/handler-route', new FuncHandler(function () { return Response::create('handled route'); })),
    ];
  }
}
