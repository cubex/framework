<?php
namespace Cubex\Console\Commands;

use Cubex\Console\ConsoleCommand;
use Packaged\Figlet\Figlet;
use Packaged\Helpers\System;
use Packaged\Helpers\ValueAs;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuiltInWebServer extends ConsoleCommand
{
  public $host = '0.0.0.0';
  public $port = 8888;
  public $showfig = true;
  public $router = 'public/index.php';

  /**
   * @short d
   * @flag
   */
  public $debug;
  /**
   * @short idekey
   */
  public $debugIdeKey = 'PHPSTORM';

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
    if(ValueAs::bool($this->showfig))
    {
      $output->write(Figlet::create('PHP WEB', 'ivrit'));
      $output->write(Figlet::create('SERVER', 'ivrit'));
    }

    $output->writeln("");
    $output->write("\tStarting on ");
    $output->write("http://");
    $output->write($this->host == '0.0.0.0' ? 'localhost' : $this->host);
    $output->write(':' . $this->port);
    $output->writeln("");

    $phpCommand = 'php';
    if($this->debug)
    {
      $phpCommand .= ' -d xdebug.remote_enable=1';
      $phpCommand .= ' -d xdebug.remote_autostart=1';
      $phpCommand .= ' -d xdebug.remote_connect_back=1';
      $phpCommand .= ' -d xdebug.idekey=' . $this->debugIdeKey;
    }

    $projectRoot = trim($this->getContext()->getProjectRoot());
    $projectRoot = $projectRoot ? '"' . $projectRoot . '"' : '';

    $command = [$phpCommand . " -S $this->host:$this->port -t"];
    $command[] = $projectRoot;
    $command[] = trim($this->router);
    $command = implode(' ', array_filter($command));

    $output->writeln(["", "\tRaw Command: $command", ""]);

    return $this->runCommand($command);
  }

  protected function runCommand($command)
  {
    $exitCode = 0;
    $method = $this->_executeMethod;
    if(System::commandExists('bash'))
    {
      // Use bash to execute if available,
      // enables CTRL+C to also kill spawned process (cygwin issue)
      $command = "bash -c '$command'";
    }
    $method($command, $exitCode);
    return $exitCode;
  }
}
