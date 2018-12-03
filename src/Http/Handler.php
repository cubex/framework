<?php
namespace Cubex\Http;

use Cubex\Context\Context;
use Packaged\Http\Request;
use Packaged\Http\Response;

interface Handler
{
  public function handle(Context $c, Response $w, Request $r);
}
