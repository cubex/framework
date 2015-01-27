<?php
namespace CubexTest\Cubex\Console;

use Cubex\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommandTest extends CommandTestCase
{
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
          'middleNames' => ['Anthony', 'James']
        ]
      )
    );
  }

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
          'middleNames' => ['Anthony', 'James']
        ]
      )
    );
  }

  public function testUnfinishedCommand()
  {
    $command = new NoDocBlockTestConsoleCommand('tester');
    $this->setExpectedException('RuntimeException');
    $this->getCommandOutput($command, []);
  }

  public function testSetCubex()
  {
    $command = new TestProcessConsoleCommand();
    $this->setExpectedException(
      'Exception',
      'Cubex is controlled by the application'
    );
    $command->setCubex(new \Cubex\Cubex());
  }
}

class NoDocBlockTestConsoleCommand extends ConsoleCommand
{
}

/**
 * Class TestConsoleCommand
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

/**
 * Class TestConsoleCommand
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
