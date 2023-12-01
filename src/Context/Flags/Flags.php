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
  protected $_globalRules = [];

  public function addRule(FlagRule $rule, string ...$flags): Flags
  {
    if(empty($flags))
    {
      $this->_globalRules[] = $rule;
    }
    else
    {
      foreach($flags as $flag)
      {
        $this->_addRule($flag, $rule);
      }
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
  public function has(string $flag, bool $default = false): bool
  {
    return $this->_flags[$flag] ?? $this->_getDefault($flag, $default);
  }

  protected function _getDefault(string $flag, bool $default): bool
  {
    $rules = array_merge($this->_globalRules, $this->_rules[$flag] ?? []);
    if(!empty($rules))
    {
      $rulesPassed = null;
      foreach($rules as $rule)
      {
        $eval = $rule->evaluate($flag);
        if($eval === null)
        {
          // Rule set has no preference, continue to next rule
          continue;
        }
        else if($eval === true)
        {
          // Set the flag to true, but allow other rules to turn this flag off
          $rulesPassed = true;
        }
        else if($eval === false)
        {
          $rulesPassed = false;
          // If any rule returns false, the flag is off
          break;
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
