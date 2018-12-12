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
  }
}
