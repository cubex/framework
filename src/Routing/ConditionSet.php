<?php
namespace Cubex\Routing;

class ConditionSet extends AbstractConditionSet implements Condition
{
  public static function i()
  {
    return new static();
  }

  public static function with(Condition ...$conditions)
  {
    $cond = new static();
    $cond->_conditions = $conditions;
    return $cond;
  }

  public function add(Condition $condition)
  {
    return $this->_add($condition);
  }
}
