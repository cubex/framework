<?php

namespace Cubex\Tests\Routing;

use Cubex\Context\Context;
use Cubex\Routing\Constraint;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ConstraintTest extends TestCase
{
  public function testConstraints()
  {
    $request = Request::create('http://www.test.com:8080/path/one', 'POST');
    $ctx = new Context($request);

    $this->assertTrue(Constraint::any()->match($ctx));

    $this->assertTrue(Constraint::port(8080)->match($ctx));
    $this->assertFalse(Constraint::port(9898)->match($ctx));

    $this->assertTrue(Constraint::protocol('http://')->match($ctx));
    $this->assertFalse(Constraint::protocol('https://')->match($ctx));

    $this->assertTrue(Constraint::scheme('http')->match($ctx));
    $this->assertFalse(Constraint::scheme('https')->match($ctx));

    $this->assertTrue(Constraint::method('POST')->match($ctx));
    $this->assertFalse(Constraint::method('GET')->match($ctx));

    $this->assertTrue(Constraint::path('/path/one')->match($ctx));
    $this->assertTrue(Constraint::path('/path')->match($ctx));
    $this->assertTrue(Constraint::path('/path', Constraint::TYPE_START)->match($ctx));
    $this->assertFalse(Constraint::path('/Path', Constraint::TYPE_START)->match($ctx));
    $this->assertFalse(Constraint::path('/path', Constraint::TYPE_EXACT)->match($ctx));
    $this->assertFalse(Constraint::path('/abc')->match($ctx));

    $this->assertTrue(Constraint::domain('test')->match($ctx));
    $this->assertTrue(Constraint::domain('test', 'com')->match($ctx));
    $this->assertFalse(Constraint::domain('test', 'net')->match($ctx));
    $this->assertFalse(Constraint::ajax()->match($ctx));

    $this->assertTrue(Constraint::subDomain('www', 'test', 'com')->match($ctx));
    $this->assertFalse(Constraint::subDomain('my', 'test', 'com')->match($ctx));

    $this->assertTrue(Constraint::port(8080)->add('ABC', null)->match($ctx));
    $this->assertFalse(Constraint::port(8080)->add('ABC', true)->match($ctx));

    $request = Request::create('HTTPS://www.tesT.com:9090/InV123');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    $this->assertTrue(Constraint::path('/INV{invoiceNumber@num}', Constraint::TYPE_MATCH)->match($ctx));
    $this->assertFalse(Constraint::path('/INV{invoiceNumber@num}', Constraint::TYPE_EXACT)->match($ctx));
    $this->assertTrue(Constraint::path('/INV')->add(Constraint::PROTOCOL, 'http', Constraint::TYPE_START)->match($ctx));
    $this->assertTrue(Constraint::path('/INV')->add(Constraint::PORT, 9090, Constraint::TYPE_EXACT)->match($ctx));
    $this->assertFalse(Constraint::path('/INV')->add(Constraint::PORT, "9090", Constraint::TYPE_EXACT)->match($ctx));
    $this->assertFalse(Constraint::path('/')->add(Constraint::DOMAIN, "Ates", Constraint::TYPE_START)->match($ctx));
    $this->assertTrue(Constraint::path('/')->add(Constraint::DOMAIN, "tes", Constraint::TYPE_START)->match($ctx));
  }

  public function testRouteData()
  {
    $request = Request::create('http://www.test.com:8080/one/two/three/4/5/s1x/s3^eN!/uncaptured', 'POST');
    $ctx = new Context($request);

    $this->assertTrue(
      Constraint::path('/{one}/{two@alpha}/{three}/{four@num}/{five@alphanum}/{six@alphanum}/{seven@all}')->match($ctx)
    );
    $this->assertEquals("one", $ctx->routeData()->get('one'));
    $this->assertEquals("two", $ctx->routeData()->get('two'));
    $this->assertEquals("three", $ctx->routeData()->get('three'));
    $this->assertEquals("4", $ctx->routeData()->get('four'));
    $this->assertEquals("5", $ctx->routeData()->get('five'));
    $this->assertEquals("s1x", $ctx->routeData()->get('six'));
    $this->assertEquals("s3^eN!", $ctx->routeData()->get('seven'));
    $this->assertEquals('/one/two/three/4/5/s1x/s3^eN!', $ctx->meta()->get(Constraint::META_ROUTED_PATH));

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    $this->assertTrue(Constraint::path('/INV{invoiceNumber@num}')->match($ctx));
    $this->assertEquals(123, $ctx->routeData()->get('invoiceNumber'));
  }
}
