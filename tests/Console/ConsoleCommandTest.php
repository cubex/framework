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
    $this->assertEquals('Tester', $command->getName());
    $this->assertEquals('This is a test command', $command->getDescription());

    $def = $command->getDefinition();
    $this->assertTrue($def->hasArgument('name'));
    $this->assertTrue($def->hasArgument('surname'));
    $this->assertTrue($def->hasArgument('middleNames'));
    $this->assertTrue($def->getArgument('middleNames')->isArray());

    $this->assertEquals('John', $def->getArgument('name')->getDefault());
    $this->assertEquals('Smith', $def->getArgument('surname')->getDefault());
    $this->assertEquals(
      ['Simon', 'Dennis'],
      $def->getArgument('middleNames')->getDefault()
    );
    $this->assertEquals(
      'longParam',
      $def->getOptionForShortcut('y')->getName()
    );

    $this->assertTrue($def->getOption('who')->isValueRequired());

    $command = new TestExecuteConsoleCommand();
    $def = $command->getDefinition();
    $this->assertEquals(
      'This should become a named parameter',
      $def->getOption('demand')->getDescription()
    );
    $this->assertFalse($def->getOption('on')->acceptValue());

    $cmd = new NoDocBlockTestConsoleCommand();
    $this->assertEquals('nodocblocktestconsolecommand', $cmd->getName());
  }

  /**
   * @throws \Exception
   */
  public function testOutput()
  {
    $command = new TestConsoleCommand();
    $this->assertContains(
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
    $this->assertContains(
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
  public function testUnfinishedCommand()
  {
    $command = new NoDocBlockTestConsoleCommand('tester');
    $this->expectException('RuntimeException');
    $this->getCommandOutput($command, []);
  }
}
