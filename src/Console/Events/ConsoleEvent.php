<?php
namespace Cubex\Console\Events;

use Cubex\Console\Console;
use Cubex\Events\ContextEvent;
use Packaged\Context\Context;

abstract class ConsoleEvent extends ContextEvent
{
  private $_console;

  public static function i(Context $ctx, Console $console = null)
  {
    $event = parent::i($ctx);
    $event->_console = $console;
    return $event;
  }

  public function getConsole(): Console
  {
    return $this->_console;
  }
}
