<?php
namespace Cubex\Tests\Supporting\Console;

use Cubex\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TestConsoleCommand
 *
 * @description This is a test command
 * @name Tester
 */
class TestConsoleCommand extends ConsoleCommand
{
  /**
   * @flag
   */
  public $on;

  /**
   * @valuerequired
   * This is the who parameter
   */
  public $who;

  /**
   * @required
   * @description This should become a named parameter
   */
  public $demand;

  /**
   * Really long parameter
   *
   * @short y
   */
  public $longParam = 'xyz';

  /**
   * This is what the execute command does
   *
   * @param InputInterface                                    $input
   * @param OutputInterface                                   $output
   * @param                                                   $name
   * @param string                                            $surname
   * @param array                                             $middleNames
   */
  protected function executeCommand(
    InputInterface $input,
    OutputInterface $output, $name = 'John',
    $surname = 'Smith',
    array $middleNames = ['Simon', 'Dennis']
  )
  {
    $output->writeln("First: $name");
    $output->writeln("Last: $surname");
    $output->writeln("Middle(s): " . implode(' ', $middleNames));
  }
}
