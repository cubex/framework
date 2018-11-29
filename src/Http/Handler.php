<?php
namespace Cubex\Http;

use Cubex\Context\Context;

interface Handler
{
  public function handle(Context $c, Response $w, Request $r);
}
