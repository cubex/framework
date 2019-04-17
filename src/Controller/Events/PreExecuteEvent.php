<?php
namespace Cubex\Controller\Events;

use Cubex\Context\Context;
use Cubex\Events\ContextEvent;

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
