<?php
namespace Cubex\Routing;

use Packaged\Http\Request;

interface Condition
{
  public function match(Request $request): bool;
}
