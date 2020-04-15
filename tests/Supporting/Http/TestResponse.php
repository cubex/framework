<?php
namespace Cubex\Tests\Supporting\Http;

use Packaged\Http\Response;

class TestResponse extends Response
{
  protected $_output;

  public function getSendResult()
  {
    return $this->_output;
  }

  public function sendContent()
  {
    $this->_output = $this->content;
    return $this;
  }

}
