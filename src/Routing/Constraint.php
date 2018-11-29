<?php
namespace Cubex\Routing;

use Cubex\Http\Request;

interface Constraint
{
  public function match(Request $request): bool;
}
