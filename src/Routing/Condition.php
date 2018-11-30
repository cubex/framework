<?php
namespace Cubex\Routing;

use Cubex\Http\Request;

interface Condition
{
  public function match(Request $request): bool;
}
