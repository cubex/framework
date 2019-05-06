<?php
namespace Cubex\Routing;

use Cubex\Context\Context;

interface RouteCompleter
{
  public function complete(Context $context);
}
