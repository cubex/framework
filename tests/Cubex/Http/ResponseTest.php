<?php
namespace CubexTest\Cubex\Http;

use Cubex\Http\Response;
use Cubex\Responses\CsvResponse;
use Illuminate\Contracts\Support\Renderable;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
  public function testExtendsSymfonyResponse()
  {
    $response = new Response();
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $response
    );
  }

  public function testSend()
  {
    $response = new Response();
    $responseSend = $response->send();
    $this->assertTrue(property_exists($responseSend, 'headers'));
    $this->assertTrue(property_exists($responseSend, 'content'));
    $this->assertTrue(property_exists($responseSend, 'version'));
    $this->assertTrue(property_exists($responseSend, 'statusCode'));
    $this->assertTrue(property_exists($responseSend, 'statusText'));
    $this->assertTrue(property_exists($responseSend, 'charset'));
  }

  public function testFromText()
  {
    $response = new Response();
    $response->fromText("Hello World");
    $this->assertStringContainsString('Content-Type:  text/plain', (string)$response);
  }

  public function testFromJson()
  {
    $response = new Response();
    $response->from(["a" => "b"]);
    $this->assertStringEndsWith('{"a":"b"}', (string)$response);
    $response->fromJson(["a" => "b", "c" => "d"]);
    $this->assertStringEndsWith('{"a":"b","c":"d"}', (string)$response);
  }

  public function testFromJsonP()
  {
    $response = new Response();
    $response->fromJsonp("phpunit", (object)["a" => "b"]);
    $this->assertStringEndsWith('phpunit({"a":"b"})', (string)$response);
  }

  public function testRenderable()
  {
    $renderable = new RenderableClass();
    $response = new Response();
    $response->from($renderable);
    $this->assertStringContainsString('rendered content', (string)$response);
  }

  public function testCubexHeaders()
  {
    if(!defined('PHP_START'))
    {
      define('PHP_START', microtime(true));
    }
    $response = new Response();
    $response->setCubexHeaders();
    $this->assertStringContainsString('X-Execution-Time', (string)$response);

    $response = new Response();
    $response->disableCubexHeaders();
    $response->setCubexHeaders();
    $this->assertStringNotContainsString('X-Execution-Time', (string)$response);

    $response = new Response();
    $response->disableCubexHeaders();
    $response->enableCubexHeaders();
    $response->setCubexHeaders();
    $this->assertStringContainsString('X-Execution-Time', (string)$response);
  }

  public function testCsvResponse()
  {
    $response = new CsvResponse(
      [
        ['a1', 'b1', 'c1'],
        ['a2', 'b2', 'c2'],
      ]
    );
    $response->setFilename('test.csv');
    $raw = (string)$response->send();
    $this->assertStringContainsString('a1,b1,c1', $raw);
  }

  public function testInvalidCsvResponse()
  {
    $response = new CsvResponse();
    $raw = (string)$response->getContent();
    $this->assertEmpty($raw);
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('You must specify an array or object when using a csv response');
    $response->setContent('this is a test');
  }
}

class RenderableClass implements Renderable
{
  public function render()
  {
    return 'rendered content';
  }
}
