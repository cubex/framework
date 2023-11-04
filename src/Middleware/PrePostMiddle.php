<?php

namespace Cubex\Middleware;

use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;
use Symfony\Component\HttpFoundation\Response;

class PrePostMiddle implements Middleware
{
  public function handle(Context $c, Handler $next): Response
  {
    var_dump("Pre Middleware");
    $response = $next->handle($c);
    var_dump("Post Middleware");
    return $response;
  }

}