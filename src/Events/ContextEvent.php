<?php
namespace Cubex\Events;

use Packaged\Context\Context;
use Packaged\Event\Events\AbstractEvent;

abstract class ContextEvent extends AbstractEvent
{
  private $_context;

  public static function i(Context $context)
  {
    $event = new static();
    $event->_context = $context;
    return $event;
  }

  public function getContext(): Context
  {
    return $this->_context;
  }

  public function getType()
  {
    return static::class;
  }
}
