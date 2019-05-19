<?php
namespace Cubex\Events\Handle;

use Cubex\Events\ContextEvent;
use Cubex\Http\Handler;
use Packaged\Context\Context;

abstract class HandlerEvent extends ContextEvent
{
  private $_handler;

  public static function i(Context $context, Handler $handler = null)
  {
    $event = parent::i($context);
    $event->_handler = $handler;
    return $event;
  }

  public function getHandler(): Handler
  {
    return $this->_handler;
  }
}
