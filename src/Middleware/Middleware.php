<?php

namespace Cubex\Middleware;

use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;
use Symfony\Component\HttpFoundation\Response;

abstract class Middleware implements MiddlewareInterface
{
  protected Handler $_next;

  public function setNext(Handler $handler): Handler
  {
    $this->_next = $handler;
    return $this;
  }

  protected function next(Context $c): Response
  {
    return $this->_next->handle($c);
  }
}