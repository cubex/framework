<?php
namespace Project;

use Cubex\Context\Context;
use Cubex\Http\Handler;
use Cubex\Http\Request;
use Cubex\Http\Response;

class DefaultHandler implements Handler
{
  public function handle(Context $c, Response $w, Request $r)
  {
    $w->from("Hello");
  }
}
