<?php

namespace Cubex\Tests\Supporting\Middleware;

use Cubex\Middleware\Middleware;
use Packaged\Context\Context;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class TestFailedMiddleware extends Middleware
{
  public function handle(Context $c): Response
  {
    return new RedirectResponse('/failed');
  }
}
