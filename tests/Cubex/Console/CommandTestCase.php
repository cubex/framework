<?php
namespace CubexTest\Cubex\Console;

use Cubex\Console\Console;
use Cubex\Console\ConsoleCommand;
use Cubex\Cubex;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class CommandTestCase extends \PHPUnit_Framework_TestCase
{
  public function getCommandOutput(
    ConsoleCommand $command, $options
  )
  {
    $console = new Console();
    $cubex   = new Cubex();
    $cubex->boot();
    $console->setCubex($cubex);
    $command->setApplication($console);
    $input  = new ArrayInput(
      array_merge(['command' => $command->getName()], $options)
    );
    $output = new BufferedOutput();

    $command->run($input, $output);
    return $output->fetch();
  }
}
