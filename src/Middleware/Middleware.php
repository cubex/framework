<?php

namespace Cubex\Middleware;

use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;
use Symfony\Component\HttpFoundation\Response;

interface Middleware
{
  public function handle(Context $c, Handler $next): Response;
}