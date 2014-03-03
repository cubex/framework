<?php

abstract class CommandTestCase extends PHPUnit_Framework_TestCase
{
  public function getCommandOutput(
    \Cubex\Console\ConsoleCommand $command, $options
  )
  {
    $console = new \Cubex\Console\Console();
    $cubex   = new \Cubex\Cubex();
    $cubex->boot();
    $console->setCubex($cubex);
    $command->setApplication($console);
    $input  = new \Symfony\Component\Console\Input\ArrayInput(
      array_merge(['command' => $command->getName()], $options)
    );
    $output = new \Symfony\Component\Console\Output\BufferedOutput();

    $command->run($input, $output);
    return $output->fetch();
  }
}
