<?php

namespace Cubex\Context\Flags;

interface FlagRule
{
  public function evaluate(string $flag): ?bool;
}
