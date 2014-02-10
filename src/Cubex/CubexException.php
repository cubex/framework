<?php
namespace Cubex;

class CubexException extends \Exception
{
  protected $_debug;

  public function setDebug($data)
  {
    $this->_debug = $data;
    return $this;
  }

  public function getDebug()
  {
    return $this->_debug;
  }

  public static function debugException($message, $code, $debugData)
  {
    $exception = new self($message, $code);
    $exception->setDebug($debugData);
    return $exception;
  }
}
