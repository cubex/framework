<?php
namespace Cubex\Events\Handle;

use Cubex\Events\ContextEvent;
use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;

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
