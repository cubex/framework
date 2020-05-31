<?php
namespace Cubex\Context\Events;

use Cubex\Console\Console;
use Packaged\Event\Events\AbstractEvent;

class ConsoleCreatedEvent extends AbstractEvent
{
  /**
   * @var Console
   */
  private $_console;

  public function __construct(Console $console)
  {
    parent::__construct();
    $this->_console = $console;
  }

  public function getType()
  {
    return static::class;
  }

  /**
   * @return Console
   */
  public function getConsole(): Console
  {
    return $this->_console;
  }
}
