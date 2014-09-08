<?php
namespace Cubex\Testing;

use Symfony\Component\HttpFoundation\Response;

class TestResponse
{
  protected $_response;

  public function __construct(Response $response)
  {
    $this->_response = $response;
  }

  public function getContent()
  {
    return $this->_response->getContent();
  }

  public function hasOriginal()
  {
    return $this->_response instanceof \Cubex\Http\Response;
  }

  public function getOriginal()
  {
    if($this->_response instanceof \Cubex\Http\Response)
    {
      return $this->_response->getOriginalResponse();
    }

    return $this->_response;
  }

  public function getHeaders()
  {
    return $this->_response->headers;
  }

  public function getResponse()
  {
    return $this->_response;
  }
}
