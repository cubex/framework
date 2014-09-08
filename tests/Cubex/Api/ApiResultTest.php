<?php
namespace CubexTest\Api;

class ApiResultTest extends \PHPUnit_Framework_TestCase
{
  public function getResponse($body)
  {
    $response = new \GuzzleHttp\Message\Response(200);
    $response->setBody(
      \GuzzleHttp\Stream\Stream::factory($body)
    );
    return $response;
  }

  public function testSuccess()
  {
    $response = $this->getResponse(
      '{"status":{"message":"","code":200},'
      . '"result":["Tasker","worker"]}'
    );
    $response->setHeader('X-Call-Time', "2.000");
    $response->setHeader('X-Execution-Time', "39.000");
    $apiResult = new \Cubex\Api\ApiResult($response, false);
    $apiResult->setTotalTime(60.000);

    $this->assertEquals(200, $apiResult->getStatusCode());
    $this->assertEquals('', $apiResult->getStatusMessage());

    $this->assertEquals(["Tasker", "worker"], $apiResult->getResult());

    $this->assertEquals(60, $apiResult->getTotalTime());
    $this->assertEquals(21, $apiResult->getTransportTime());
    $this->assertEquals(2, $apiResult->getCallTime());
    $this->assertEquals(39, $apiResult->getExecutionTime());

    $exception = $apiResult->getException();
    $this->assertEquals(200, $exception->getCode());
    $this->assertEquals('', $exception->getMessage());
    $this->assertFalse($apiResult->isError());
  }

  public function testFailure()
  {
    $response = $this->getResponse(
      '{"status":{"message":"Broken","code":500},'
      . '"result":""}'
    );
    $response->setHeader('X-Call-Time', "2.000");
    $response->setHeader('X-Execution-Time', "39.000");
    $apiResult = new \Cubex\Api\ApiResult($response, false);
    $apiResult->setTotalTime(60.000);

    $this->assertEquals(500, $apiResult->getStatusCode());
    $this->assertEquals('Broken', $apiResult->getStatusMessage());

    $this->assertEquals('', $apiResult->getResult());

    $this->assertEquals(60, $apiResult->getTotalTime());
    $this->assertEquals(21, $apiResult->getTransportTime());
    $this->assertEquals(2, $apiResult->getCallTime());
    $this->assertEquals(39, $apiResult->getExecutionTime());

    $exception = $apiResult->getException();
    $this->assertEquals(500, $exception->getCode());
    $this->assertEquals('Broken', $exception->getMessage());
    $this->assertTrue($apiResult->isError());
  }

  public function testExceptions()
  {
    $this->setExpectedException('Exception', 'Not Found', 404);
    $response = $this->getResponse(
      '{"status":{"message":"Not Found","code":404},'
      . '"result":"",'
      . '"profile":{"callTime":"2.684","executionTime":"38.341"}}'
    );
    new \Cubex\Api\ApiResult($response, true);
  }

  public function testInvalidPayload()
  {
    $this->setExpectedException(
      'Exception',
      'Unable to decode json string',
      500
    );
    $response = $this->getResponse('Internal Server Error');
    new \Cubex\Api\ApiResult($response);
  }

  public function testInvalidJson()
  {
    $this->setExpectedException('Exception', 'Invalid json / api result', 500);
    $response = $this->getResponse(
      '{"status":{"message":"Not Found","code":404}}'
    );
    new \Cubex\Api\ApiResult($response);
  }
}
