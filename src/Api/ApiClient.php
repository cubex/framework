<?php
namespace Cubex\Api;

use Cubex\CubexException;
use GuzzleHttp\ClientInterface;
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

  public function __construct($baseUri, ClientInterface $guzzle = null)
  {
    $this->_baseUri = $baseUri;
    $this->_guzzle  = $guzzle === null ? new Client() : $guzzle;
  }

  /**
   * @param $call
   *
   * @return ApiResult
   */
  public function get($call)
  {
    $time     = microtime(true);
    $response = $this->_guzzle->get(build_path($this->_baseUri, $call));
    return $this->_processResponse($response, $time);
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
