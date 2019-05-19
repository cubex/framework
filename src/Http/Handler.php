<?php
namespace Cubex\Http;

use Packaged\Context\Context;
use Symfony\Component\HttpFoundation\Response;

interface Handler
{
  public function handle(Context $c): Response;
}
