<?php
namespace Cubex\Console\Commands;

use Cubex\Console\ConsoleCommand;
use Packaged\Figlet\Figlet;
use Packaged\Helpers\System;
use Packaged\Helpers\ValueAs;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_filter;
use function fclose;
use function fsockopen;
use function implode;
use function is_resource;
use function trim;

class BuiltInWebServer extends ConsoleCommand
{
  public $host;
  /**
   * @short p
   */
  public $port;
  public $showfig = true;
  public $router = 'public/index.php';

  /**
   * @short c
   */
  public $cubexLocalSubDomain;

  /**
   * Defaulted to true if no port has been specified
   *
   * @flag
   */
  public $useNextAvailablePort;

  /**
   * @flag
   */
  public $showCommand;

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
    if(empty($this->host))
    {
      if($this->cubexLocalSubDomain)
      {
        $this->host = $this->cubexLocalSubDomain . '.cubex-local.com';
      }
      else
      {
        $this->host = $this->debug ? '0.0.0.0' : 'localhost';
      }
    }

    if($this->port === null)
    {
      $this->port = 8888;
      $this->useNextAvailablePort = true;
    }

    if($this->useNextAvailablePort)
    {
      for($i = 0; $i < 100; $i++)
      {
        if($this->_isPortAvailable($this->port))
        {
          break;
        }
        $this->port++;
      }
    }

    if(ValueAs::bool($this->showfig))
    {
      $output->write(Figlet::create('PHP WEB', 'ivrit'));
      $output->write(Figlet::create('SERVER', 'ivrit'));
    }

    return $this->_runCommand($this->_buildCommand($output));
  }

  protected function _isPortAvailable($portNumber): bool
  {
    $errno = $errStr = null;
    $res = @fsockopen('localhost', $portNumber, $errno, $errStr, 0.1);
    if(is_resource($res))
    {
      // @codeCoverageIgnoreStart
      fclose($res);
      // @codeCoverageIgnoreEnd
    }
    return $res === false;
  }

  protected function _runCommand($command)
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

  protected function _buildCommand(OutputInterface $output)
  {
    $output->writeln("");
    $output->write("\tStarting Server at ");
    $output->write("http://");
    $output->write($this->host === '0.0.0.0' ? '127.0.0.1' : $this->host);
    $output->writeln(':' . $this->port);

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

    if($this->showCommand)
    {
      $output->writeln(["", "\tRaw Command: $command", ""]);
    }

    return $command;
  }
}
