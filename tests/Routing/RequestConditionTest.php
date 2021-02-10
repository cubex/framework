<?php

namespace Cubex\Tests\Routing;

use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Routing\RequestCondition;
use PHPUnit\Framework\TestCase;

class RequestConditionTest extends TestCase
{
  public function testConstraints()
  {
    $request = Request::create('http://www.test.com:8080/path/one', 'POST');
    $ctx = new Context($request);

    self::assertTrue(RequestCondition::i()->match($ctx));

    self::assertTrue(RequestCondition::i()->port(8080)->match($ctx));
    self::assertFalse(RequestCondition::i()->port(9898)->match($ctx));

    self::assertTrue(RequestCondition::i()->scheme('http')->match($ctx));
    self::assertFalse(RequestCondition::i()->scheme('https')->match($ctx));

    self::assertTrue(RequestCondition::i()->method('POST')->match($ctx));
    self::assertFalse(RequestCondition::i()->method('GET')->match($ctx));

    self::assertTrue(RequestCondition::i()->path('/path/one')->match($ctx));
    self::assertTrue(RequestCondition::i()->path('/path')->match($ctx));
    self::assertTrue(RequestCondition::i()->path('/path', RequestCondition::TYPE_START)->match($ctx));
    self::assertFalse(RequestCondition::i()->path('/Path', RequestCondition::TYPE_START)->match($ctx));
    self::assertFalse(RequestCondition::i()->path('/path', RequestCondition::TYPE_EXACT)->match($ctx));
    self::assertFalse(RequestCondition::i()->path('/abc')->match($ctx));

    self::assertTrue(RequestCondition::i()->domain('test')->match($ctx));
    self::assertTrue(RequestCondition::i()->domain('test')->tld('com')->match($ctx));
    self::assertFalse(RequestCondition::i()->domain('test')->tld('net')->match($ctx));
    self::assertFalse(RequestCondition::i()->ajax()->match($ctx));

    self::assertTrue(RequestCondition::i()->subDomain('www')->domain('test')->tld('com')->match($ctx));
    self::assertFalse(RequestCondition::i()->subDomain('my')->domain('test')->tld('com')->match($ctx));

    $request = Request::create('HTTPS://www.tesT.com:9090/InV123');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    self::assertTrue(
      RequestCondition::i()->path('/INV{invoiceNumber@num}', RequestCondition::TYPE_MATCH)->match($ctx)
    );
    self::assertFalse(
      RequestCondition::i()->path('/INV{invoiceNumber@num}', RequestCondition::TYPE_EXACT)->match($ctx)
    );
    self::assertTrue(
      RequestCondition::i()->path('/INV', RequestCondition::TYPE_START_CASEI)
        ->scheme('http', RequestCondition::TYPE_START)
        ->match($ctx)
    );
    self::assertTrue(
      RequestCondition::i()->path('/INV', RequestCondition::TYPE_START_CASEI)
        ->port(9090)
        ->match($ctx)
    );
    self::assertFalse(
      RequestCondition::i()->path('/INV', RequestCondition::TYPE_START_CASEI)
        ->port("9090", RequestCondition::TYPE_EXACT)
        ->match($ctx)
    );

    $request = Request::create('HTTPS://www.tesT.com:9090/?x=y&abc');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    self::assertTrue(
      RequestCondition::i()->path('/')->domain("tes", RequestCondition::TYPE_START)->match($ctx)
    );
    self::assertFalse(
      RequestCondition::i()->path('/page')->domain("tes", RequestCondition::TYPE_START)->match($ctx)
    );
    self::assertFalse(
      RequestCondition::i()->path('')->domain("Ates", RequestCondition::TYPE_START)->match($ctx)
    );
    self::assertTrue(
      RequestCondition::i()->path('')->domain("tes", RequestCondition::TYPE_START)->match($ctx)
    );
    self::assertTrue(RequestCondition::i()->hasQueryKey("abc")->match($ctx));
    self::assertTrue(RequestCondition::i()->hasQueryValue("x", "y")->match($ctx));
    self::assertTrue(RequestCondition::i()->hostname("www.test.com")->match($ctx));
    self::assertTrue(RequestCondition::i()->rootDomain("test.com")->match($ctx));
  }

  public function testRouteData()
  {
    $request = Request::create(
      'http://www.test.com:8080/one/two/three/4/5/s1x/s3^eN!/all/remain/path/end/finished',
      'POST'
    );
    $ctx = new Context($request);

    $constraint = RequestCondition::i()->path(
      '/{one}/{two@alpha}/{three}/{four@num}/{five@alphanum}/{six@alphanum}/{seven}/{remain@all}/end'
    );
    self::assertTrue($constraint->match($ctx));
    $constraint->complete($ctx);

    self::assertEquals("one", $ctx->routeData()->get('one'));
    self::assertEquals("two", $ctx->routeData()->get('two'));
    self::assertEquals("three", $ctx->routeData()->get('three'));
    self::assertEquals("4", $ctx->routeData()->get('four'));
    self::assertEquals("5", $ctx->routeData()->get('five'));
    self::assertEquals("s1x", $ctx->routeData()->get('six'));
    self::assertEquals("s3^eN!", $ctx->routeData()->get('seven'));
    self::assertEquals("all/remain/path", $ctx->routeData()->get('remain'));
    self::assertEquals(
      '/one/two/three/4/5/s1x/s3^eN!/all/remain/path/end',
      $ctx->meta()->get(RequestCondition::META_ROUTED_PATH)
    );

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    $constraint2 = RequestCondition::i()->path('/INV{invoiceNumber@num}');
    self::assertTrue($constraint2->match($ctx));
    $constraint2->complete($ctx);
    self::assertEquals(123, $ctx->routeData()->get('invoiceNumber'));
  }

  public function testRegexInConstraintVariable()
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid regex passed to path #^/{test#test}(?=/|$)#ui');

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    RequestCondition::i()->path('/{test#test}')->match($ctx);
  }
}
