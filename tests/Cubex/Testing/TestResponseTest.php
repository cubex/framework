<?php
namespace CubexTest\Cubex\Testing;

use Cubex\Http\Response;
use Cubex\Testing\TestResponse;

class TestResponseTest extends \PHPUnit_Framework_TestCase
{
  public function testAccessors()
  {
    $response = new Response('Some Content', 200, ['test' => 'head']);
    $tr       = new TestResponse($response);
    $this->assertEquals('Some Content', $tr->getContent());
    $this->assertTrue($tr->getHeaders()->has('test'));
    $this->assertEquals('head', $tr->getHeaders()->get('test'));
    $this->assertTrue($tr->hasOriginal());
    $this->assertSame($response, $tr->getResponse());
    $this->assertEquals('Some Content', $tr->getOriginal());
  }

  public function testNonCubexResponse()
  {
    $response = new \Symfony\Component\HttpFoundation\Response(
      'Some Content',
      200,
      ['test' => 'head']
    );
    $tr       = new TestResponse($response);
    $this->assertEquals('Some Content', $tr->getContent());
    $this->assertTrue($tr->getHeaders()->has('test'));
    $this->assertEquals('head', $tr->getHeaders()->get('test'));
    $this->assertFalse($tr->hasOriginal());
    $this->assertSame($response, $tr->getResponse());
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $tr->getOriginal()
    );
  }
}
