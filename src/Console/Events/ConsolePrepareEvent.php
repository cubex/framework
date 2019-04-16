<?php
namespace Cubex\Console\Events;

use Cubex\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsolePrepareEvent extends ConsoleEvent
{
  private $_input;
  private $_output;

  public static function i(Console $console, InputInterface $input = null, OutputInterface $output = null)
  {
    $event = parent::i($console);
    $event->_input = $input;
    $event->_output = $output;
    return $event;
  }

  public function getInput(): InputInterface
  {
    return $this->_input;
  }

  public function getOutput(): OutputInterface
  {
    return $this->_output;
  }
}
