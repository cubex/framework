<?php
namespace Cubex\Http;

use Packaged\Context\Context;
use Symfony\Component\HttpFoundation\Response;

class ExceptionHandler implements Handler
{
  protected $_exception;

  public function __construct(\Throwable $e)
  {
    $this->_exception = $e;
  }

  public function handle(Context $c): Response
  {
    $e = $this->_exception;
    $r = new Response($e->getMessage(), $e->getCode() >= 400 ? $e->getCode() : 500);
    return $r;
  }
}
