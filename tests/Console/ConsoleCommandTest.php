<?php
namespace Cubex\Tests\Console;

use Cubex\Tests\Supporting\Console\NoDocBlockTestConsoleCommand;
use Cubex\Tests\Supporting\Console\TestConsoleCommand;
use Cubex\Tests\Supporting\Console\TestExecuteConsoleCommand;
use Cubex\Tests\Supporting\Console\TestProcessConsoleCommand;

class ConsoleCommandTest extends ConsoleCommandTestCase
{
  /**
   * @throws \Exception
   */
  public function testClassDocBlocks()
  {
    $command = new TestConsoleCommand();
    self::assertEquals('Tester', $command->getName());
    self::assertEquals('This is a test command', $command->getDescription());

    $def = $command->getDefinition();
    self::assertTrue($def->hasArgument('name'));
    self::assertTrue($def->hasArgument('surname'));
    self::assertTrue($def->hasArgument('middleNames'));
    self::assertTrue($def->getArgument('middleNames')->isArray());

    self::assertEquals('John', $def->getArgument('name')->getDefault());
    self::assertEquals('Smith', $def->getArgument('surname')->getDefault());
    self::assertEquals(
      ['Simon', 'Dennis'],
      $def->getArgument('middleNames')->getDefault()
    );
    self::assertEquals(
      'longParam',
      $def->getOptionForShortcut('y')->getName()
    );

    self::assertTrue($def->getOption('who')->isValueRequired());

    $command = new TestExecuteConsoleCommand();
    $def = $command->getDefinition();
    self::assertEquals(
      'This should become a named parameter',
      $def->getOption('demand')->getDescription()
    );
    self::assertFalse($def->getOption('on')->acceptValue());

    $cmd = new NoDocBlockTestConsoleCommand();
    self::assertEquals('nodocblocktestconsolecommand', $cmd->getName());
  }

  /**
   * @throws \Exception
   */
  public function testOutput()
  {
    $command = new TestConsoleCommand();
    self::assertStringContainsString(
      'First: Brooke
Last: Bryan
Middle(s): Anthony James',
      $this->getCommandOutput(
        $command,
        [
          'name'        => 'Brooke',
          'surname'     => 'Bryan',
          'middleNames' => ['Anthony', 'James'],
        ]
      )
    );
  }

  /**
   * @throws \Exception
   */
  public function testOutputProcess()
  {
    $command = new TestProcessConsoleCommand();
    self::assertStringContainsString(
      'First: Brooke
Last: Bryan
Middle(s): Anthony James',
      $this->getCommandOutput(
        $command,
        [
          'name'        => 'Brooke',
          'surname'     => 'Bryan',
          'middleNames' => ['Anthony', 'James'],
        ]
      )
    );
  }

  /**
   * @throws \RuntimeException
   */
  public function testUnfinishedCommand()
  {
    $command = new NoDocBlockTestConsoleCommand('tester');
    $this->expectException('RuntimeException');
    $this->getCommandOutput($command, []);
  }
}
