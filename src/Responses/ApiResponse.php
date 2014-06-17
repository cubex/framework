<?php
namespace Cubex\Responses;

use Cubex\Http\Response;

class ApiResponse extends Response
{
  protected $_statusMessage = '';
  protected $_statusCode = 200;

  protected $_callTime;
  protected $_phpTime;

  /**
   * Set the status/error message
   *
   * @param $message
   * @param $code
   *
   * @return $this
   */
  public function setStatus($message, $code)
  {
    $this->_statusMessage = $message;
    $this->_statusCode    = $code;
    return $this;
  }

  public function getStatusCode()
  {
    return $this->_statusCode;
  }

  public function getStatusMessage()
  {
    return $this->_statusMessage;
  }

  /**
   * Set the execution time for the call
   *
   * @param $time
   *
   * @return $this
   */
  public function setCallTime($time)
  {
    $this->_callTime = $time;
    return $this;
  }

  /**
   * Set the execution time for the php thread
   *
   * @param $time
   *
   * @return $this
   */
  public function setExecutionTime($time)
  {
    $this->_phpTime = $time;
    return $this;
  }

  /**
   * Store the raw content
   *
   * @param mixed $content
   *
   * @return $this|\Symfony\Component\HttpFoundation\Response
   */
  public function setContent($content)
  {
    $this->headers->set("Content-Type", "application/json");
    $this->content = $content;
    return $this;
  }

  /**
   * Send the api response to the user
   *
   * @return $this|\Symfony\Component\HttpFoundation\Response
   */
  public function sendContent()
  {
    echo $this->getJson();
    return $this;
  }

  /**
   * Retrieve the json response
   *
   * @return string
   */
  public function getJson()
  {
    return json_encode($this->_buildResponseObject());
  }

  /**
   * Form the object to json encode
   *
   * @return \stdClass
   */
  protected function _buildResponseObject()
  {
    $result                  = new \stdClass();
    $result->status          = new \stdClass();
    $result->status->message = $this->_statusMessage;
    $result->status->code    = $this->_statusCode;

    $result->result = $this->getContent();

    $result->profile                = new \stdClass();
    $result->profile->callTime      = $this->_callTime;
    $result->profile->executionTime = $this->_phpTime;

    return $result;
  }
}
