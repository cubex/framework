<?php
namespace Cubex\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;

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

  protected $_headers;

  protected $_throwExceptions = true;

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
        'error'    => function (ErrorEvent $event)
        {
          $batchId = $event->getRequest()->getHeader('X-Batch-ID');
          $result  = $this->_results[$batchId];
          if($result instanceof ApiResult)
          {
            $result->readJson($event->getResponse()->getBody());
          }
        },
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

  /**
   * @param       $call
   * @param array $params
   *
   * @return ApiResult
   */
  public function post($call, array $params)
  {
    return $this->callApi($call, 'POST', ['body' => $params]);
  }

  public function callApi($call, $method = 'GET', array $options = [])
  {
    $time    = microtime(true);
    $request = $this->_guzzle->createRequest(
      $method,
      build_path_unix($this->_baseUri, $call),
      $options
    );

    if($this->_headers)
    {
      foreach($this->_headers as $header => $value)
      {
        $request->addHeader($header, $value);
      }
    }

    if(!$this->isBatchOpen())
    {
      try
      {
        return $this->_processResponse($this->_guzzle->send($request), $time);
      }
      catch(RequestException $e)
      {
        $response = $e->getResponse();
        if($response)
        {
          return $this->_processResponse($response, $time);
        }
        else
        {
          throw $e;
        }
      }
    }

    $batchId = uniqid($method, true);
    $request->addHeader('X-Batch-ID', $batchId);

    $apiResult                = new ApiResult();
    $this->_batch[]           = $request;
    $this->_results[$batchId] = $apiResult;

    return $apiResult;
  }

  public function addGlobalHeader($headerKey, $value)
  {
    $this->_headers[$headerKey] = $value;
    return $this;
  }

  public function removeGlobalHeader($headerKey)
  {
    unset($this->_headers[$headerKey]);
    return $this;
  }

  /**
   * Specify if the API result should throw exceptions when received
   *
   * @param bool $throw
   *
   * @return $this
   */
  public function shouldThrowExceptions($throw = true)
  {
    $this->_throwExceptions = $throw;
    return $this;
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
    $apiResult = new ApiResult($response, $this->_throwExceptions);
    $apiResult->setTotalTime(
      number_format((microtime(true) - $time) * 1000, 3)
    );
    return $apiResult;
  }
}
