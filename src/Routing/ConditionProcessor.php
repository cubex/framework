<?php

namespace Cubex\Routing;

use Cubex\Attributes\PreCondition;
use Cubex\Attributes\SkipCondition;
use Packaged\Context\Context;
use Packaged\Context\ContextAwareTrait;
use Packaged\DiContainer\AttributeWatcher;
use Packaged\DiContainer\DependencyInjector;
use Packaged\DiContainer\ReflectionInterrupt;
use Packaged\DiContainer\ReflectionObserver;

class ConditionProcessor extends AttributeWatcher implements ReflectionInterrupt, ReflectionObserver
{
  use ContextAwareTrait;

  protected ?DependencyInjector $_di = null;

  public function __construct(Context $ctx, ?DependencyInjector $di = null)
  {
    $this->setContext($ctx);
    $this->_di = $di;
    $this->clear();
  }

  /**
   * @var \Cubex\Attributes\PreCondition[]
   */
  protected array $_conditions = [];
  protected bool $_processed = false;

  protected mixed $_handled = null;

  protected function _processAttributes()
  {
    if($this->_processed)
    {
      return;
    }

    $skipClasses = [];
    $this->_conditions = [];
    foreach($this->attributes() as $attribute)
    {
      if($attribute->getName() === SkipCondition::class)
      {
        $skipClasses[] = $attribute->newInstance()->getClass();
      }
      if($attribute->getName() === PreCondition::class)
      {
        /** @var \Cubex\Attributes\AbstractConditionAttribute $condition */
        $condition = $attribute->newInstance();
        $this->_conditions[$condition->getClass()] = $condition;
      }
    }

    foreach($skipClasses as $skipClass)
    {
      if(isset($this->_conditions[$skipClass]))
      {
        unset($this->_conditions[$skipClass]);
      }
    }

    $this->_processed = true;
  }

  public function shouldInterruptMethod(): bool
  {
    $this->_processAttributes();
    if(empty($this->_conditions))
    {
      return false;
    }

    foreach($this->_conditions as $condition)
    {
      $inst = $condition->result($this->_di);
      $res = $inst->process($this->getContext());
      if($res !== null)
      {
        $this->_handled = $res;
        return true;
      }
    }

    return false;
  }

  public function interruptMethod(): mixed
  {
    return $this->_handled;
  }
}
