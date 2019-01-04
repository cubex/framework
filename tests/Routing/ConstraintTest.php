<?php

namespace Cubex\Tests\Routing;

use Cubex\Context\Context;
use Cubex\Routing\Constraint;
use Cubex\Routing\HttpConstraint;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ConstraintTest extends TestCase
{
  public function testConstraints()
  {
    $request = Request::create('http://www.test.com:8080/path/one', 'POST');
    $ctx = new Context($request);

    $this->assertTrue(HttpConstraint::any()->match($ctx));

    $this->assertTrue(HttpConstraint::port(8080)->match($ctx));
    $this->assertFalse(HttpConstraint::port(9898)->match($ctx));

    $this->assertTrue(HttpConstraint::scheme('http')->match($ctx));
    $this->assertFalse(HttpConstraint::scheme('https')->match($ctx));

    $this->assertTrue(HttpConstraint::method('POST')->match($ctx));
    $this->assertFalse(HttpConstraint::method('GET')->match($ctx));

    $this->assertTrue(HttpConstraint::path('/path/one')->match($ctx));
    $this->assertTrue(HttpConstraint::path('/path')->match($ctx));
    $this->assertTrue(HttpConstraint::path('/path', Constraint::TYPE_START)->match($ctx));
    $this->assertFalse(HttpConstraint::path('/Path', Constraint::TYPE_START)->match($ctx));
    $this->assertFalse(HttpConstraint::path('/path', Constraint::TYPE_EXACT)->match($ctx));
    $this->assertFalse(HttpConstraint::path('/abc')->match($ctx));

    $this->assertTrue(HttpConstraint::domain('test')->match($ctx));
    $this->assertTrue(HttpConstraint::domain('test', 'com')->match($ctx));
    $this->assertFalse(HttpConstraint::domain('test', 'net')->match($ctx));
    $this->assertFalse(HttpConstraint::ajax()->match($ctx));

    $this->assertTrue(HttpConstraint::subDomain('www', 'test', 'com')->match($ctx));
    $this->assertFalse(HttpConstraint::subDomain('my', 'test', 'com')->match($ctx));

    $this->assertTrue(HttpConstraint::port(8080)->add('ABC', null)->match($ctx));
    $this->assertFalse(HttpConstraint::port(8080)->add('ABC', true)->match($ctx));

    $request = Request::create('HTTPS://www.tesT.com:9090/InV123');
    $request->server->set('HTTPS', 'on');
    $ctx = new Context($request);
    $this->assertTrue(HttpConstraint::path('/INV{invoiceNumber@num}', Constraint::TYPE_MATCH)->match($ctx));
    $this->assertFalse(HttpConstraint::path('/INV{invoiceNumber@num}', Constraint::TYPE_EXACT)->match($ctx));
    $this->assertTrue(
      HttpConstraint::path('/INV')->add(Constraint::SCHEME, 'http', Constraint::TYPE_START)->match($ctx)
    );
    $this->assertTrue(HttpConstraint::path('/INV')->add(Constraint::PORT, 9090, Constraint::TYPE_EXACT)->match($ctx));
    $this->assertFalse(
      HttpConstraint::path('/INV')->add(Constraint::PORT, "9090", Constraint::TYPE_EXACT)->match($ctx)
    );
    $this->assertFalse(HttpConstraint::path('/')->add(Constraint::DOMAIN, "Ates", Constraint::TYPE_START)->match($ctx));
    $this->assertTrue(HttpConstraint::path('/')->add(Constraint::DOMAIN, "tes", Constraint::TYPE_START)->match($ctx));
  }

  public function testRouteData()
  {
    $request = Request::create(
      'http://www.test.com:8080/one/two/three/4/5/s1x/s3^eN!/all/remain/path/end/finished',
      'POST'
    );
    $ctx = new Context($request);

    $this->assertTrue(
      HttpConstraint::path(
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
      $ctx->meta()->get(Constraint::META_ROUTED_PATH)
    );

    $request = Request::create('http://www.test.com:8080/INV123');
    $ctx = new Context($request);
    $this->assertTrue(HttpConstraint::path('/INV{invoiceNumber@num}')->match($ctx));
    $this->assertEquals(123, $ctx->routeData()->get('invoiceNumber'));
  }
}
