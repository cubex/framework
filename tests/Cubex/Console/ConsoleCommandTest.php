<?php

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
    $def     = $command->getDefinition();
    $this->assertEquals(
      'This should become a named parameter',
      $def->getArgument('demand')->getDescription()
    );
    $this->assertFalse($def->getOption('on')->acceptValue());

    $this->setExpectedException(
      'LogicException',
      'The command name cannot be empty.'
    );
    new NoDocBlockTestConsoleCommand();
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

  public function testUnfinishedCommand()
  {
    $command = new NoDocBlockTestConsoleCommand('tester');
    $this->setExpectedException(
      'RuntimeException',
      'This command has nothing to do'
    );
    $this->getCommandOutput($command, []);
  }
}

class NoDocBlockTestConsoleCommand extends \Cubex\Console\ConsoleCommand
{
}

/**
 * Class TestConsoleCommand
 * @description This is a test command
 * @name Tester
 */
class TestConsoleCommand extends \Cubex\Console\ConsoleCommand
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
   * @param \Symfony\Component\Console\Input\InputInterface   $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param                                                   $name
   * @param string                                            $surname
   * @param array                                             $middleNames
   */
  protected function executeCommand(
    \Symfony\Component\Console\Input\InputInterface $input,
    \Symfony\Component\Console\Output\OutputInterface $output, $name = 'John',
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
 * Class TestConsoleCommand
 * @description This is a test command
 * @name Tester
 */
class TestExecuteConsoleCommand extends \Cubex\Console\ConsoleCommand
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
   * @param \Symfony\Component\Console\Input\InputInterface   $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *
   * @return void
   */
  protected function execute(
    \Symfony\Component\Console\Input\InputInterface $input,
    \Symfony\Component\Console\Output\OutputInterface $output
  )
  {
  }
}
