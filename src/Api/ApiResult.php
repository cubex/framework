<?php
namespace Cubex\Api;

use GuzzleHttp\Message\ResponseInterface;

class ApiResult
{
  protected $_statusMessage;
  protected $_statusCode;
  protected $_result;
  protected $_executionTime;
  protected $_callTime;
  protected $_totalTime;

  /**
   * @param string $response  raw json response
   * @param bool   $throw Should throw API errors as exceptions
   *
   * @throws \Exception
   */
  public function __construct(ResponseInterface $response = null, $throw = true)
  {
    if($response !== null)
    {
      $this->_executionTime = $response->getHeader('X-Execution-Time');
      $this->_callTime = $response->getHeader('X-Call-Time');
      $this->readJson($response->getBody(), $throw);
    }
  }

  /**
   * @param string $json  Raw JSON Response from the api
   * @param bool   $throw Throw errors received from the API
   *
   * @throws \Exception
   */
  public function readJson($json, $throw = true)
  {
    $result = json_decode($json);

    if(json_last_error() !== JSON_ERROR_NONE)
    {
      throw new \RuntimeException("Unable to decode json string", 500);
    }

    if(!isset(
    $result->status,
    $result->status->message,
    $result->status->code,
    $result->result)
    )
    {
      throw new \RuntimeException("Invalid json / api result", 500);
    }

    $this->_statusMessage = $result->status->message;
    $this->_statusCode    = $result->status->code;

    if($throw && $this->_statusCode !== 200)
    {
      throw new \Exception($this->_statusMessage, $this->_statusCode);
    }

    $this->_result        = $result->result;
    $this->_callTime      = $result->profile->callTime;
    $this->_executionTime = $result->profile->executionTime;
  }

  /**
   * @param double $time Time in ms taking to process the request
   *
   * @return $this
   */
  public function setTotalTime($time)
  {
    $this->_totalTime = $time;
    return $this;
  }

  /**
   * API Call result
   *
   * @return mixed
   */
  public function getResult()
  {
    return $this->_result;
  }

  /**
   * Get the time taken to process the call internally on the server
   *
   * @return mixed
   */
  public function getCallTime()
  {
    return $this->_callTime;
  }

  /**
   * Get the time taken to process the whole thread on the server
   *
   * @return mixed
   */
  public function getExecutionTime()
  {
    return $this->_executionTime;
  }

  /**
   * Get the amount of time spent in the network
   *
   * @return mixed
   */
  public function getTransportTime()
  {
    return $this->_totalTime - $this->_executionTime;
  }

  /**
   * Get the total time taking to make and retrieve the request
   *
   * @return mixed
   */
  public function getTotalTime()
  {
    return $this->_totalTime;
  }

  /**
   * Error message
   *
   * @return mixed
   */
  public function getStatusMessage()
  {
    return $this->_statusMessage;
  }

  /**
   * status code
   *
   * @return mixed
   */
  public function getStatusCode()
  {
    return $this->_statusCode;
  }

  /**
   * The status message/code as an exception
   *
   * @return \Exception
   */
  public function getException()
  {
    return new \Exception($this->_statusMessage, $this->_statusCode);
  }

  /**
   * Did the call error?
   *
   * @return bool
   */
  public function isError()
  {
    return $this->_statusCode !== 200;
  }
}
