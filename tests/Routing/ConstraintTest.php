<?php

namespace Cubex\Tests\Routing;

use Cubex\Routing\Constraint;
use Packaged\Http\Request;
use PHPUnit\Framework\TestCase;

class ConstraintTest extends TestCase
{
  public function testConstraints()
  {
    $request = Request::create('http://www.test.com:8080/path/one', 'POST');

    $this->assertTrue(Constraint::any()->match($request));

    $this->assertTrue(Constraint::port(8080)->match($request));
    $this->assertFalse(Constraint::port(9898)->match($request));

    $this->assertTrue(Constraint::protocol('http://')->match($request));
    $this->assertFalse(Constraint::protocol('https://')->match($request));

    $this->assertTrue(Constraint::scheme('http')->match($request));
    $this->assertFalse(Constraint::scheme('https')->match($request));

    $this->assertTrue(Constraint::method('POST')->match($request));
    $this->assertFalse(Constraint::method('GET')->match($request));

    $this->assertTrue(Constraint::path('/path/one')->match($request));
    $this->assertTrue(Constraint::path('/path')->match($request));
    $this->assertTrue(Constraint::path('/path', Constraint::TYPE_START)->match($request));
    $this->assertFalse(Constraint::path('/Path', Constraint::TYPE_START)->match($request));
    $this->assertFalse(Constraint::path('/path', Constraint::TYPE_EXACT)->match($request));
    $this->assertFalse(Constraint::path('/abc')->match($request));

    $this->assertTrue(Constraint::domain('test')->match($request));
    $this->assertTrue(Constraint::domain('test', 'com')->match($request));
    $this->assertFalse(Constraint::domain('test', 'net')->match($request));
    $this->assertFalse(Constraint::ajax()->match($request));

    $this->assertTrue(Constraint::subDomain('www', 'test', 'com')->match($request));
    $this->assertFalse(Constraint::subDomain('my', 'test', 'com')->match($request));

    $this->assertTrue(Constraint::port(8080)->add('ABC', null)->match($request));
    $this->assertFalse(Constraint::port(8080)->add('ABC', true)->match($request));
  }
}
