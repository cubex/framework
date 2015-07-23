<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Cubex;
use Cubex\Http\Request;
use Cubex\Kernel\EndpointKernel;
use CubexTest\InternalCubexTestCase;
use Packaged\Api\Abstracts\AbstractApiResponse;
use Packaged\Api\Exceptions\ApiException;

class EndpointKernelTest extends InternalCubexTestCase
{
  protected function getCubex()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    return $cubex;
  }

  public function testExceptions()
  {
    $kernel = new MockEndpointKernel();
    $kernel->setCubex($this->getCubex());
    $result = $kernel->handle(Request::create('/error', 'GET', []));
    $this->assertContains('json', $result->headers->get('content-type'));
    $content = $result->getContent();
    $this->assertJson($content);
    $this->assertContains(
      'status":{"code":500,"message":"Something Failed"}',
      $content
    );
    $this->assertContains(
      '"type":"\\\\Packaged\\\\Api\\\\Exceptions\\\\ApiException"',
      $content
    );
  }

  public function testResult()
  {
    $kernel = new MockEndpointKernel();
    $kernel->setCubex($this->getCubex());
    $result = $kernel->handle(Request::create('/result', 'GET', []));
    $this->assertContains('json', $result->headers->get('content-type'));
    $this->assertEquals(
      '{"status":{"code":200,"message":""},'
      . '"type":"\CubexTest\Cubex\Kernel\MockEndpointResult"'
      . ',"result":{"name":"Test"}}',
      stripcslashes($result->getContent())
    );
  }
}

class MockEndpointKernel extends EndpointKernel
{
  public function getError()
  {
    throw new ApiException('Something Failed', 500);
  }

  public function getResult()
  {
    $result = new MockEndpointResult();
    $result->name = 'Test';
    return $result;
  }
}

class MockEndpointResult extends AbstractApiResponse
{
  public $name;
}
