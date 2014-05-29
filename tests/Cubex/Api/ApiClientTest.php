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
  }
}
