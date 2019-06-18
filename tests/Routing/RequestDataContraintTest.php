<?php

namespace Cubex\Tests\Routing;

use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Routing\RequestDataCondition;
use PHPUnit\Framework\TestCase;

class RequestDataContraintTest extends TestCase
{
  protected function _makeContext()
  {
    return new Context(
      Request::create(
        'https://localhost/?query1=val1&query2=val2&query3=val3',
        'POST',
        ['post1' => 'val1', 'post2' => 'val2', 'post3' => 'val3'],
        ['cookie1' => 'val1', 'cookie2' => 'val2'],
        [],
        ['HTTPS' => 'on']
      )
    );
  }

  public function testInstance()
  {
    $c = $this->_makeContext();
    $i = RequestDataCondition::i();

    $this->assertTrue($i->match($c));
    $i->post('post1', 'val1');
    $this->assertTrue($i->match($c));
    $i->server('HTTPS');
    $this->assertTrue($i->match($c));
    $i->query('query2', 'val2');
    $this->assertTrue($i->match($c));
    $i->cookie('cookie2');
    $this->assertTrue($i->match($c));
    $i->cookie('cookie1', 'val1');
    $this->assertTrue($i->match($c));
    $i->post('missing');
    $this->assertFalse($i->match($c));
  }

  public function testServer()
  {
    $this->assertTrue(RequestDataCondition::i()->server('HTTPS')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->server('HTTPS', 'on')->match($this->_makeContext()));
    $this->assertFalse(RequestDataCondition::i()->server('HTTP')->match($this->_makeContext()));
    $this->assertFalse(RequestDataCondition::i()->server('HTTPS', 'OFF')->match($this->_makeContext()));
  }

  public function testCookie()
  {
    $this->assertFalse(RequestDataCondition::i()->cookie('cookieN')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->cookie('cookie1')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->cookie('cookie1', 'val1')->match($this->_makeContext()));
    $this->assertTrue(
      RequestDataCondition::i()->cookie('cookie1', 'val1')->cookie('cookie2', 'val2')->match($this->_makeContext())
    );
  }

  public function testPost()
  {
    $this->assertFalse(RequestDataCondition::i()->post('postN')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->post('post1')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->post('post1', 'val1')->match($this->_makeContext()));
    $this->assertTrue(
      RequestDataCondition::i()->post('post1', 'val1')->post('post2', 'val2')->match($this->_makeContext())
    );
  }

  public function testQuery()
  {
    $this->assertFalse(RequestDataCondition::i()->query('queryN')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->query('query1')->match($this->_makeContext()));
    $this->assertTrue(RequestDataCondition::i()->query('query1', 'val1')->match($this->_makeContext()));
    $this->assertTrue(
      RequestDataCondition::i()->query('query1', 'val1')->query('query2', 'val2')->match($this->_makeContext())
    );
  }
}
