<?php

namespace Cubex\Middleware;

use Packaged\Routing\Handler\Handler;

interface MiddlewareInterface extends Handler
{
  public function setNext(Handler $handler): Handler;
}