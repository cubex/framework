<?php

class ApiClientTest extends PHPUnit_Framework_TestCase
{
  public function testGet()
  {
    $json = '{"error":{"message":"","code":200},'
      . '"result":["Tasker","worker"],'
      . '"profile":{"callTime":"2.000","executionTime":"39.000"}}';

    $response = new \GuzzleHttp\Message\Response(
      200,
      [],
      \GuzzleHttp\Stream\Stream::factory($json)
    );
    $adapter  = new \GuzzleHttp\Adapter\MockAdapter();
    $adapter->setResponse($response);
    $guzzler = new \GuzzleHttp\Client(['adapter' => $adapter]);

    $client    = new \Cubex\Api\ApiClient('http://www.test.com', $guzzler);
    $apiResult = $client->get('');
    $this->assertInstanceOf('\Cubex\Api\ApiResult', $apiResult);
    $this->assertEquals(['Tasker', 'worker'], $apiResult->getResult());
  }

  public function testBatch()
  {
    $json = '{"error":{"message":"","code":200},'
      . '"result":["Tasker","worker"],'
      . '"profile":{"callTime":"2.000","executionTime":"39.000"}}';

    $response = new \GuzzleHttp\Message\Response(
      200,
      [],
      \GuzzleHttp\Stream\Stream::factory($json)
    );

    $adapter = new \GuzzleHttp\Adapter\MockAdapter();
    $adapter->setResponse($response);
    $parAdapter = new \GuzzleHttp\Adapter\FakeParallelAdapter($adapter);
    $guzzler    = new \GuzzleHttp\Client(['parallel_adapter' => $parAdapter]);

    $client = new \Cubex\Api\ApiClient('http://www.test.com', $guzzler);

    $client->openBatch();
    $apiResult  = $client->get('/');
    $apiResult2 = $client->get('/');

    $this->assertInstanceOf('\Cubex\Api\ApiResult', $apiResult);
    $this->assertInstanceOf('\Cubex\Api\ApiResult', $apiResult2);
    $this->assertNull($apiResult->getResult());
    $this->assertNull($apiResult2->getResult());

    $client->runBatch();

    $this->assertEquals(['Tasker', 'worker'], $apiResult->getResult());
    $this->assertEquals(['Tasker', 'worker'], $apiResult2->getResult());
  }

  public function testBatchOpenClose()
  {
    $client = new \Cubex\Api\ApiClient('');
    $this->assertFalse($client->isBatchOpen());
    $client->openBatch();
    $this->assertTrue($client->isBatchOpen());
    $client->closeBatch();
    $this->assertFalse($client->isBatchOpen());
  }
}
