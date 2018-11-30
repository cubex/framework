<?php
namespace Cubex\Http;

use Cubex\Context\Context;

class ExceptionHandler implements Handler
{
  protected $_exception;

  public function __construct(\Exception $e)
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
