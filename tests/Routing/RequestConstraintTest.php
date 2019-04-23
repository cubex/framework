<?php

namespace Cubex\Tests\Routing;

use Cubex\Context\Context;
use Cubex\Routing\RequestConstraint;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class RequestConstraintTest extends TestCase
{
  public function testConstraints()
  {
    $request = Request::create('http://www.test.com:8080/path/one', 'POST');
    $ctx = new Context($request);

    $this->assertTrue(RequestConstraint::i()->match($ctx));

    $this->assertTrue(RequestConstraint::i()->port(8080)->match($ctx));
    $this->assertFalse(RequestConstraint::i()->port(9898)->match($ctx));

    $this->assertTrue(RequestConstraint::i()->scheme('http')->match($ctx));
    $this->assertFalse(RequestConstraint::i()->scheme('https')->match($ctx));

    $this->assertTrue(RequestConstraint::i()->method('POST')->match($ctx));
    $this->assertFalse(RequestConstraint::i()->method('GET')->match($ctx));

    $this->assertTrue(RequestConstraint::i()->path('/path/one')->match($ctx));
    $this->assertTrue(RequestConstraint::i()->path('/path')->match($ctx));
    $this->assertTrue(RequestConstraint::i()->path('/path', RequestConstraint::TYPE_START)->match($ctx));
    $this->assertFalse(RequestConstraint::i()->path('/Path', RequestConstraint::TYPE_START)->match($ctx));
    $this->assertFalse(RequestConstraint::i()->path('/path', RequestConstraint::TYPE_EXACT)->match($ctx));
    $this->assertFalse(RequestConstraint::i()->path('/abc')->match($ctx));

    $this->assertTrue(RequestConstraint::i()->domain('test')->match($ctx));
    $this->assertTrue(RequestConstraint::i()->domain('test')->tld('com')->match($ctx));
    $this->assertFalse(RequestConstraint::i()->domain('test')->tld('net')->match($ctx));
    $this->assertFalse(RequestConstraint::i()->ajax()->match($ctx));

    $this->assertTrue(RequestConstraint::i()->subDomain('www')->domain('test')->tld('com')->match($ctx));
    $this->assertFalse(RequestConstraint::i()->subDomain('my')->domain('test')->tld('com')->match($ctx));

    $request = Request::create('HTTPS://www.tesT.com:9090/InV123');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    $this->assertTrue(
      RequestConstraint::i()->path('/INV{invoiceNumber@num}', RequestConstraint::TYPE_MATCH)->match($ctx)
    );
    $this->assertFalse(
      RequestConstraint::i()->path('/INV{invoiceNumber@num}', RequestConstraint::TYPE_EXACT)->match($ctx)
    );
    $this->assertTrue(
      RequestConstraint::i()->path('/INV', RequestConstraint::TYPE_START_CASEI)
        ->scheme('http', RequestConstraint::TYPE_START)
        ->match($ctx)
    );
    $this->assertTrue(
      RequestConstraint::i()->path('/INV', RequestConstraint::TYPE_START_CASEI)
        ->port(9090)
        ->match($ctx)
    );
    $this->assertFalse(
      RequestConstraint::i()->path('/INV', RequestConstraint::TYPE_START_CASEI)
        ->port("9090", RequestConstraint::TYPE_EXACT)
        ->match($ctx)
    );

    $request = Request::create('HTTPS://www.tesT.com:9090/?x=y&abc');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    $this->assertTrue(
      RequestConstraint::i()->path('/')->domain("tes", RequestConstraint::TYPE_START)->match($ctx)
    );
    $this->assertFalse(
      RequestConstraint::i()->path('/page')->domain("tes", RequestConstraint::TYPE_START)->match($ctx)
    );
    $this->assertFalse(
      RequestConstraint::i()->path('')->domain("Ates", RequestConstraint::TYPE_START)->match($ctx)
    );
    $this->assertTrue(
      RequestConstraint::i()->path('')->domain("tes", RequestConstraint::TYPE_START)->match($ctx)
    );
    $this->assertTrue(RequestConstraint::i()->hasQueryKey("abc")->match($ctx));
    $this->assertTrue(RequestConstraint::i()->hasQueryValue("x", "y")->match($ctx));
    $this->assertTrue(RequestConstraint::i()->hostname("www.test.com")->match($ctx));
    $this->assertTrue(RequestConstraint::i()->rootDomain("test.com")->match($ctx));
  }

  public function testRouteData()
  {
    $request = Request::create(
      'http://www.test.com:8080/one/two/three/4/5/s1x/s3^eN!/all/remain/path/end/finished',
      'POST'
    );
    $ctx = new Context($request);

    $this->assertTrue(
      RequestConstraint::i()->path(
        '/{one}/{two@alpha}/{three}/{four@num}/{five@alphanum}/{six@alphanum}/{seven}/{remain@all}/end'
      )->match($ctx)
    );
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
      $ctx->meta()->get(RequestConstraint::META_ROUTED_PATH)
    );

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    $this->assertTrue(RequestConstraint::i()->path('/INV{invoiceNumber@num}')->match($ctx));
    $this->assertEquals(123, $ctx->routeData()->get('invoiceNumber'));
  }

  public function testRegexInConstraintVariable()
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid regex passed to path #^/{test#test}(?=/|$)#ui');

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    RequestConstraint::i()->path('/{test#test}')->match($ctx);
  }
}
