<?php

namespace Cubex\Middleware;

use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;
use Symfony\Component\HttpFoundation\Response;

class Middlewares implements Handler
{
  /** @var \Cubex\Middleware\Middleware[] */
  protected array $_middlewares = [];
  protected ?Handler $_handler;

  public function __construct()
  {
    $this->_middlewares[] = new PrePostMiddle();
  }

  public function setHandler(Handler $handler)
  {
    $this->_handler = $handler;
    return $this;
  }

  public function handle(Context $c): Response
  {
    $next = $this->_handler;
    foreach($this->_middlewares as $middleware)
    {
      $next = function (Context $c) use ($middleware, $next) {
        return $middleware->handle($c, $next);
      };
    }
    return $next($c);
  }

}