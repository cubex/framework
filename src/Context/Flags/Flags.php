<?php

namespace Cubex\Context\Flags;

/**
 * Flags
 */
class Flags
{
  /**
   * @var array string[] $_flags
   */
  protected $_flags = [];
  protected $_rules = [];

  public function addRule(FlagRule $rule, string ...$flags): Flags
  {
    foreach($flags as $flag)
    {
      $this->_addRule($flag, $rule);
    }
    return $this;
  }

  protected function _addRule(string $flag, FlagRule $rule): Flags
  {
    if(!isset($this->_rules[$flag]))
    {
      $this->_rules[$flag] = [];
    }
    $this->_rules[$flag][] = $rule;
    return $this;
  }

  public function set(string $flag, bool $value): Flags
  {
    $this->_flags[$flag] = $value;
    return $this;
  }

  public function enable(string $flag): Flags
  {
    return $this->set($flag, true);
  }

  public function disable(string $flag): Flags
  {
    return $this->set($flag, false);
  }

  public function remove(string $flag): Flags
  {
    unset($this->_flags[$flag]);
    return $this;
  }

  /**
   * @param string $flag
   * @param bool   $default value of the flag if not set
   *
   * @return bool
   */
  public function has(string $flag, bool $default): bool
  {
    return $this->_flags[$flag] ?? $this->_getDefault($flag, $default);
  }

  protected function _getDefault(string $flag, bool $default): bool
  {
    if(isset($this->_rules[$flag]))
    {
      $rulesPassed = null;
      foreach($this->_rules[$flag] as $rule)
      {
        switch($rule->evaluate($flag))
        {
          case true:
            $rulesPassed = true;
            break;
          case false:
            $rulesPassed = false;
            break 2;
        }
      }
      if($rulesPassed !== null)
      {
        // Set the rule once rules are evaluated
        $this->set($flag, $rulesPassed);
      }
    }
    return $rulesPassed ?? $default;
  }
}
