<?php
namespace Cubex\Tests\Console;

use Cubex\Console\ConsoleCommand;
use Cubex\Cubex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleCommandTestCase extends TestCase
{
  /**
   * @param ConsoleCommand $command
   * @param                $options
   *
   * @return string
   * @throws \Exception
   */
  public function getCommandOutput(ConsoleCommand $command, $options)
  {
    $cubex = new Cubex(__DIR__, null, false);
    $console = $cubex->getConsole();
    $command->setApplication($console);
    $input = new ArrayInput(
      array_merge(['command' => $command->getName()], $options)
    );
    $output = new BufferedOutput();

    $command->run($input, $output);
    return $output->fetch();
  }
}
