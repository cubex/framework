<?php
namespace Cubex\Tests\Supporting\Console;

use Cubex\Console\ConsoleCommand;

/**
 * @name Tester
 */
class TestProcessConsoleCommand extends ConsoleCommand
{
  /**
   * @param        $name
   * @param string $surname
   * @param array  $middleNames
   */
  public function process(
    $name, $surname = 'Smith', array $middleNames = ['Simon', 'Dennis']
  )
  {
    $this->_output->writeln("First: $name");
    $this->_output->writeln("Last: $surname");
    $this->_output->writeln("Middle(s): " . implode(' ', $middleNames));
  }
}

