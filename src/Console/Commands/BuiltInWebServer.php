<?php
namespace Cubex\Console\Commands;

use Cubex\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuiltInWebServer extends ConsoleCommand
{
  public $host = '0.0.0.0';
  public $port = 8080;
  public $router = 'public/index.php';

  protected $_executeMethod = 'passthru';

  protected function configure()
  {
    $this->setName('serve');
    $this->setDescription("Execute the built in PHP web server");
  }

  /**
   * @inheritdoc
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int|mixed|null
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln("");
    $output->write("Starting a built in web server on ");
    $output->write("http://");
    $output->write($this->host == '0.0.0.0' ? 'localhost' : $this->host);
    $output->write(':' . $this->port);
    $output->writeLn("");

    $command   = ["php -S $this->host:$this->port -t"];
    $command[] = trim($this->getCubex()->getProjectRoot());
    $command[] = trim($this->router);
    $command   = implode(' ', array_filter($command));

    $output->writeln(["", "Raw Command: $command", ""]);

    return $this->runCommand($command);
  }

  protected function runCommand($command)
  {
    $exitCode = 0;
    $method   = $this->_executeMethod;
    $method($command, $exitCode);
    return $exitCode;
  }
}
