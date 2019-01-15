<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Http\FuncHandler;
use Cubex\Tests\Supporting\Container\TestObject;
use Packaged\Http\Response;
use Packaged\Http\Responses\AccessDeniedResponse;

class TestArrayRouteController extends TestController
{
  public function getRoutes()
  {
    return [
      self::route('/route', 'route'),
      self::route('/ui', 'ui'),
      self::route('/buffered', 'buffered'),
      self::route('/response', 'response'),
      self::route('/missing', 'missing'),
      self::route('/exception', 'exception'),
      self::route('/sub/call', [SubTestController::class, 'remoteCall']),
      self::route('/sub', SubTestController::class),
      self::route('/badsub', TestObject::class),
      self::route('/default-response', AccessDeniedResponse::class),
      self::route('/handler-route', new FuncHandler(function () { return Response::create('handled route'); })),
    ];
  }
}
