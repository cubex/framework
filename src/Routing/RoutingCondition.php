<?php
namespace Cubex\Routing;

use Cubex\Http\Handler;

interface RoutingCondition extends Constraint
{
  public function getHandler(): Handler;
}
