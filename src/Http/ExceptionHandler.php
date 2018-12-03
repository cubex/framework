<?php
namespace Cubex\Http;

use Cubex\Context\Context;
use Packaged\Http\Request;
use Packaged\Http\Response;

class ExceptionHandler implements Handler
{
  protected $_exception;

  public function __construct(\Throwable $e)
  {
    $this->_exception = $e;
  }

  public function handle(Context $c, Response $w, Request $r)
  {
    $e = $this->_exception;
    $w->setStatusCode($e->getCode() >= 400 ? $e->getCode() : 500, $e->getMessage())
      ->setContent($e->getMessage())
      ->prepare($r)
      ->send();
  }

}
