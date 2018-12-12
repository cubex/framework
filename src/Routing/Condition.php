<?php
namespace Cubex\Routing;

use Cubex\Context\Context;

interface Condition
{
  public function match(Context $context): bool;
}
