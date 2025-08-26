<?php

namespace Cubex\Tests\Supporting\Middleware;

use Cubex\Middleware\Middleware;
use Packaged\Context\Context;
use Symfony\Component\HttpFoundation\Response;

class SecondTestMiddleware extends Middleware
{
  public function handle(Context $c): Response
  {
    return $this->next($c);
  }
}
