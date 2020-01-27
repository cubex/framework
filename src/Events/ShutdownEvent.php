<?php
namespace Cubex\Events;

use Packaged\Event\Events\AbstractEvent;

class ShutdownEvent extends AbstractEvent
{
  public function getType()
  {
    return static::class;
  }
}
