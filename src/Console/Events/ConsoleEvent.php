<?php
namespace Cubex\Console\Events;

use Cubex\Console\Console;
use Packaged\Event\Events\AbstractEvent;

abstract class ConsoleEvent extends AbstractEvent
{
  private $_console;

  public static function i(Console $console)
  {
    $event = new static();
    $event->_console = $console;
    return $event;
  }

  public function getConsole(): Console
  {
    return $this->_console;
  }

  public function getType()
  {
    return static::class;
  }

}
