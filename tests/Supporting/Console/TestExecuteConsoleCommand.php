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
class TestExecuteConsoleCommand extends ConsoleCommand
{
  /**
   * @flag
   */
  public $on;

  /**
   * This is the who parameter
   */
  public $who;

  /**
   * @required
   * @description This should become a named parameter
   */
  public $demand;

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return void
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
  }
}
