<?php
namespace Cubex\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Client;

/**
 * You must require "guzzlehttp/guzzle" within your composer.json file
 */
class ApiClient
{
  protected $_baseUri;
  /**
   * @var ClientInterface
   */
  protected $_guzzle;

  protected $_batchOpen;
  protected $_batch;
  protected $_results;

  public function __construct($baseUri, ClientInterface $guzzle = null)
  {
    $this->_baseUri = $baseUri;
    $this->_guzzle  = $guzzle === null ? new Client() : $guzzle;
  }

  public function openBatch()
  {
    $this->_batchOpen = true;
  }

  public function closeBatch()
  {
    $this->_batchOpen = false;
  }

  public function runBatch()
  {
    $this->_guzzle->sendAll(
      $this->_batch,
      [
        'complete' => function (CompleteEvent $event)
        {
          $batchId = $event->getRequest()->getHeader('X-Batch-ID');
          $result  = $this->_results[$batchId];
          if($result instanceof ApiResult)
          {
            $result->readJson($event->getResponse()->getBody());
          }
        }
      ]
    );
    $this->_batch   = [];
    $this->_results = [];
  }

  public function isBatchOpen()
  {
    return (bool)$this->_batchOpen;
  }

  /**
   * @param $call
   *
   * @return ApiResult
   */
  public function get($call)
  {
    return $this->callApi($call, 'GET');
  }

  public function callApi($call, $method = 'GET')
  {
    $time    = microtime(true);
    $request = $this->_guzzle->createRequest(
      $method,
      build_path($this->_baseUri, $call)
    );

    $batchId = uniqid($method);
    $request->addHeader('X-Batch-ID', $batchId);

    if(!$this->isBatchOpen())
    {
      return $this->_processResponse($this->_guzzle->send($request), $time);
    }

    $apiResult                = new ApiResult();
    $this->_batch[]           = $request;
    $this->_results[$batchId] = $apiResult;

    return $apiResult;
  }

  /**
   * Process the raw guzzle response
   *
   * @param ResponseInterface $response
   * @param                   $time
   *
   * @return ApiResult
   */
  protected function _processResponse(ResponseInterface $response, $time)
  {
    $apiResult = new ApiResult($response->getBody(), true);
    $apiResult->setTotalTime(
      number_format((microtime(true) - $time) * 1000, 3)
    );
    return $apiResult;
  }
}
