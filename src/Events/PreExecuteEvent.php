<?php
namespace Cubex\Events;

use Packaged\Context\Context;

class PreExecuteEvent extends ContextEvent
{
  private $_handler;

  public static function i(Context $context, $handlerResult = null)
  {
    $event = parent::i($context);
    $event->_handler = $handlerResult;
    return $event;
  }

  public function getHandler()
  {
    return $this->_handler;
  }
}
