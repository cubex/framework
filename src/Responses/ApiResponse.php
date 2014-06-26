<?php
namespace Cubex\Responses;

use Cubex\Http\Response;

class ApiResponse extends Response
{
  protected $_statusMessage = '';
  protected $_statusCode = 200;

  protected $_callTime;

  public function __construct($content = '', $status = 200, $headers = array())
  {
    parent::__construct($content, $status, $headers);
    $this->_callTime = microtime(true);
  }

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

  public function sendHeaders()
  {
    if(defined('PHP_START'))
    {
      $this->headers->set(
        'X-Execution-Time',
        number_format((microtime(true) - PHP_START) * 1000, 3)
      );
    }
    $this->headers->set(
      'X-Call-Time',
      number_format((microtime(true) - $this->_callTime) * 1000, 3)
    );
    return parent::sendHeaders();
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
    $result = new \stdClass();

    $result->status          = new \stdClass();
    $result->status->code    = $this->_statusCode;
    $result->status->message = $this->_statusMessage;

    $result->result = $this->getContent();

    return $result;
  }
}
