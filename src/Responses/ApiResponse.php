<?php
namespace Cubex\Responses;

use Cubex\Http\Response;

class ApiResponse extends Response
{
  protected $_errorMessage = '';
  protected $_errorCode = 200;

  protected $_callTime;
  protected $_phpTime;

  /**
   * Set the error message
   *
   * @param $message
   * @param $code
   *
   * @return $this
   */
  public function setError($message, $code)
  {
    $this->_errorMessage = $message;
    $this->_errorCode    = $code;
    return $this;
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
    $result                 = new \stdClass();
    $result->error          = new \stdClass();
    $result->error->message = $this->_errorMessage;
    $result->error->code    = $this->_errorCode;

    $result->result = $this->getContent();

    $result->profile                = new \stdClass();
    $result->profile->callTime      = $this->_callTime;
    $result->profile->executionTime = $this->_phpTime;

    return $result;
  }
}
