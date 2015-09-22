<?php
namespace CubexTest\Cubex\Http;

use Cubex\Http\Response;
use Cubex\Responses\CsvResponse;
use Illuminate\Support\Contracts\RenderableInterface;

class ResponseTest extends \PHPUnit_Framework_TestCase
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
    $this->assertObjectHasAttribute('headers', $responseSend);
    $this->assertObjectHasAttribute('content', $responseSend);
    $this->assertObjectHasAttribute('version', $responseSend);
    $this->assertObjectHasAttribute('statusCode', $responseSend);
    $this->assertObjectHasAttribute('statusText', $responseSend);
    $this->assertObjectHasAttribute('charset', $responseSend);
  }

  public function testFromText()
  {
    $response = new Response();
    $response->fromText("Hello World");
    $this->assertContains('Content-Type:  text/plain', (string)$response);
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
    $this->assertContains('rendered content', (string)$response);
  }

  public function testCubexHeaders()
  {
    if(!defined('PHP_START'))
    {
      define('PHP_START', microtime(true));
    }
    $response = new Response();
    $response->setCubexHeaders();
    $this->assertContains('X-Execution-Time', (string)$response);

    $response = new Response();
    $response->disableCubexHeaders();
    $response->setCubexHeaders();
    $this->assertNotContains('X-Execution-Time', (string)$response);

    $response = new Response();
    $response->disableCubexHeaders();
    $response->enableCubexHeaders();
    $response->setCubexHeaders();
    $this->assertContains('X-Execution-Time', (string)$response);
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
    $this->assertContains('a1,b1,c1', $raw);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage You must specify an array or object when using a csv response
   */
  public function testInvalidCsvResponse()
  {
    $response = new CsvResponse();
    $raw = (string)$response->getContent();
    $this->assertEmpty($raw);
    $response->setContent('this is a test');
  }
}

class RenderableClass implements RenderableInterface
{
  public function render()
  {
    return 'rendered content';
  }
}
