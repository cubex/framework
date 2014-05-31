<?php
namespace Cubex\Api;

class ApiResult
{
  protected $_errorMessage;
  protected $_errorCode;
  protected $_result;
  protected $_executionTime;
  protected $_callTime;
  protected $_totalTime;

  /**
   * @param string $json  raw json response
   * @param bool   $throw Should throw API errors as exceptions
   *
   * @throws \Exception
   */
  public function __construct($json = null, $throw = true)
  {
    if($json !== null)
    {
      $this->readJson($json, $throw);
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

    if(!isset($result->error)
      || !isset($result->error->message)
      || !isset($result->error->code)
      || !isset($result->profile)
      || !isset($result->profile->callTime)
      || !isset($result->profile->executionTime)
      || !isset($result->result)
    )
    {
      throw new \RuntimeException("Invalid json / api result", 500);
    }

    $this->_errorMessage = $result->error->message;
    $this->_errorCode    = $result->error->code;

    if($throw && $this->_errorCode !== 200)
    {
      throw new \Exception($this->_errorMessage, $this->_errorCode);
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
  public function getErrorMessage()
  {
    return $this->_errorMessage;
  }

  /**
   * Error code
   *
   * @return mixed
   */
  public function getErrorCode()
  {
    return $this->_errorCode;
  }

  /**
   * The error message/code as an exception
   *
   * @return \Exception
   */
  public function getException()
  {
    return new \Exception($this->_errorMessage, $this->_errorCode);
  }

  /**
   * Did the call error?
   *
   * @return bool
   */
  public function isError()
  {
    return $this->_errorCode !== 200;
  }
}
