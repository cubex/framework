<?php

namespace Cubex\Attributes;

use Packaged\Context\Context;

interface ConditionResult
{
  // Returns null or a value to be returned from the method
  public function process(Context $ctx): mixed;
}
