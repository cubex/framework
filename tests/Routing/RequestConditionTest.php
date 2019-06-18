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

    $this->assertTrue(RequestCondition::i()->match($ctx));

    $this->assertTrue(RequestCondition::i()->port(8080)->match($ctx));
    $this->assertFalse(RequestCondition::i()->port(9898)->match($ctx));

    $this->assertTrue(RequestCondition::i()->scheme('http')->match($ctx));
    $this->assertFalse(RequestCondition::i()->scheme('https')->match($ctx));

    $this->assertTrue(RequestCondition::i()->method('POST')->match($ctx));
    $this->assertFalse(RequestCondition::i()->method('GET')->match($ctx));

    $this->assertTrue(RequestCondition::i()->path('/path/one')->match($ctx));
    $this->assertTrue(RequestCondition::i()->path('/path')->match($ctx));
    $this->assertTrue(RequestCondition::i()->path('/path', RequestCondition::TYPE_START)->match($ctx));
    $this->assertFalse(RequestCondition::i()->path('/Path', RequestCondition::TYPE_START)->match($ctx));
    $this->assertFalse(RequestCondition::i()->path('/path', RequestCondition::TYPE_EXACT)->match($ctx));
    $this->assertFalse(RequestCondition::i()->path('/abc')->match($ctx));

    $this->assertTrue(RequestCondition::i()->domain('test')->match($ctx));
    $this->assertTrue(RequestCondition::i()->domain('test')->tld('com')->match($ctx));
    $this->assertFalse(RequestCondition::i()->domain('test')->tld('net')->match($ctx));
    $this->assertFalse(RequestCondition::i()->ajax()->match($ctx));

    $this->assertTrue(RequestCondition::i()->subDomain('www')->domain('test')->tld('com')->match($ctx));
    $this->assertFalse(RequestCondition::i()->subDomain('my')->domain('test')->tld('com')->match($ctx));

    $request = Request::create('HTTPS://www.tesT.com:9090/InV123');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    $this->assertTrue(
      RequestCondition::i()->path('/INV{invoiceNumber@num}', RequestCondition::TYPE_MATCH)->match($ctx)
    );
    $this->assertFalse(
      RequestCondition::i()->path('/INV{invoiceNumber@num}', RequestCondition::TYPE_EXACT)->match($ctx)
    );
    $this->assertTrue(
      RequestCondition::i()->path('/INV', RequestCondition::TYPE_START_CASEI)
        ->scheme('http', RequestCondition::TYPE_START)
        ->match($ctx)
    );
    $this->assertTrue(
      RequestCondition::i()->path('/INV', RequestCondition::TYPE_START_CASEI)
        ->port(9090)
        ->match($ctx)
    );
    $this->assertFalse(
      RequestCondition::i()->path('/INV', RequestCondition::TYPE_START_CASEI)
        ->port("9090", RequestCondition::TYPE_EXACT)
        ->match($ctx)
    );

    $request = Request::create('HTTPS://www.tesT.com:9090/?x=y&abc');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    $this->assertTrue(
      RequestCondition::i()->path('/')->domain("tes", RequestCondition::TYPE_START)->match($ctx)
    );
    $this->assertFalse(
      RequestCondition::i()->path('/page')->domain("tes", RequestCondition::TYPE_START)->match($ctx)
    );
    $this->assertFalse(
      RequestCondition::i()->path('')->domain("Ates", RequestCondition::TYPE_START)->match($ctx)
    );
    $this->assertTrue(
      RequestCondition::i()->path('')->domain("tes", RequestCondition::TYPE_START)->match($ctx)
    );
    $this->assertTrue(RequestCondition::i()->hasQueryKey("abc")->match($ctx));
    $this->assertTrue(RequestCondition::i()->hasQueryValue("x", "y")->match($ctx));
    $this->assertTrue(RequestCondition::i()->hostname("www.test.com")->match($ctx));
    $this->assertTrue(RequestCondition::i()->rootDomain("test.com")->match($ctx));
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
    $this->assertTrue($constraint->match($ctx));
    $constraint->complete($ctx);

    $this->assertEquals("one", $ctx->routeData()->get('one'));
    $this->assertEquals("two", $ctx->routeData()->get('two'));
    $this->assertEquals("three", $ctx->routeData()->get('three'));
    $this->assertEquals("4", $ctx->routeData()->get('four'));
    $this->assertEquals("5", $ctx->routeData()->get('five'));
    $this->assertEquals("s1x", $ctx->routeData()->get('six'));
    $this->assertEquals("s3^eN!", $ctx->routeData()->get('seven'));
    $this->assertEquals("all/remain/path", $ctx->routeData()->get('remain'));
    $this->assertEquals(
      '/one/two/three/4/5/s1x/s3^eN!/all/remain/path/end',
      $ctx->meta()->get(RequestCondition::META_ROUTED_PATH)
    );

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    $constraint2 = RequestCondition::i()->path('/INV{invoiceNumber@num}');
    $this->assertTrue($constraint2->match($ctx));
    $constraint2->complete($ctx);
    $this->assertEquals(123, $ctx->routeData()->get('invoiceNumber'));
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
