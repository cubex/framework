<?php
namespace Cubex\Routing;

use Packaged\Context\Context;

interface RouteCompleter
{
  public function complete(Context $context);
}
