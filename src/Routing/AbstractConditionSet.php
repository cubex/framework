<?php
namespace Cubex\Routing;

use Cubex\Context\Context;

abstract class AbstractConditionSet implements Condition
{
  protected $_conditions = [];

  public function match(Context $context): bool
  {
    foreach($this->_conditions as $condition)
    {
      if(!$condition->match($context))
      {
        return false;
      }
    }
    return true;
  }

  protected function _add(Condition $condition)
  {
    $this->_conditions[] = $condition;
    return $this;
  }
}
