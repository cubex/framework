<?php

namespace Cubex\Context\Flags;

class RuleSet implements FlagRule
{
  protected $_returns = [];

  public function set(string $flag, bool $value): RuleSet
  {
    $this->_returns[$flag] = $value;
    return $this;
  }

  public function evaluate(string $flag): ?bool
  {
    return $this->_returns[$flag] ?? null;
  }

  public function apply(Flags $to): Flags
  {
    $to->addRule($this, ...array_keys($this->_returns));
    return $to;
  }
}
