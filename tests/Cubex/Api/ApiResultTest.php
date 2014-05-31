<?php

class ApiResultTest extends PHPUnit_Framework_TestCase
{
  public function testSuccess()
  {
    $json      = '{"error":{"message":"","code":200},'
      . '"result":["Tasker","worker"],'
      . '"profile":{"callTime":"2.000","executionTime":"39.000"}}';
    $apiResult = new \Cubex\Api\ApiResult($json, false);
    $apiResult->setTotalTime(60.000);

    $this->assertEquals(200, $apiResult->getErrorCode());
    $this->assertEquals('', $apiResult->getErrorMessage());

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
    $json      = '{"error":{"message":"Broken","code":500},'
      . '"result":"",'
      . '"profile":{"callTime":"2.000","executionTime":"39.000"}}';
    $apiResult = new \Cubex\Api\ApiResult($json, false);
    $apiResult->setTotalTime(60.000);

    $this->assertEquals(500, $apiResult->getErrorCode());
    $this->assertEquals('Broken', $apiResult->getErrorMessage());

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
    $json = '{"error":{"message":"Not Found","code":404},'
      . '"result":"",'
      . '"profile":{"callTime":"2.684","executionTime":"38.341"}}';
    new \Cubex\Api\ApiResult($json, true);
  }

  public function testInvalidPayload()
  {
    $this->setExpectedException(
      'Exception',
      'Unable to decode json string',
      500
    );
    $json = 'Internal Server Error';
    new \Cubex\Api\ApiResult($json);
  }

  public function testInvalidJson()
  {
    $this->setExpectedException('Exception', 'Invalid json / api result', 500);
    $json = '{"error":{"message":"Not Found","code":404},'
      . '"result":""'
      . '}';
    new \Cubex\Api\ApiResult($json);
  }
}
