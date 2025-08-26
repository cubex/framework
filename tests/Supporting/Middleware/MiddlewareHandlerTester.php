<?php

namespace Cubex\Tests\Supporting\Middleware;

use Cubex\Middleware\MiddlewareHandler;

class MiddlewareHandlerTester extends MiddlewareHandler
{
  protected MiddlewareHandler $handler;

  public static function with(MiddlewareHandler $handler): self
  {
    $h = new self($handler);
    $h->handler = $handler;
    return $h;
  }

  public function getMiddlewares(): array
  {
    return $this->handler->_middlewares;
  }
}
