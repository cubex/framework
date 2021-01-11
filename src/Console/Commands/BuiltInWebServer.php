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
   * Number of workers
   * php>=7.4
   *
   * @short w
   */
  public $workers = 5;

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
        $this->host = $this->getContext()->config()->getItem('serve', 'host', $this->debug ? '0.0.0.0' : '127.0.0.1');
      }
    }

    if($this->port === null)
    {
      $this->port = $this->getContext()->config()->getItem('serve', 'port', 8888);
      $this->useNextAvailablePort = !$this->getContext()->config()->hasItem('serve', 'port');
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

    putenv('PHP_CLI_SERVER_WORKERS=' . $this->workers);
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
      $command = addcslashes($command, "'");
      $command = "bash -c $'$command'";
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

    $phpCommand = PHP_BINARY;
    if($this->debug)
    {
      // check for xdebug, this must be checked in a new process in case this was launched with different options
      $xdebugLoaded = $this->_runCommand($phpCommand . ' -r "exit(extension_loaded(\'xdebug\')?0:1);"');
      if($xdebugLoaded !== 0)
      {
        $ext = ' -d zend_extension=xdebug';
        $xdebugLoaded = $this->_runCommand($phpCommand . $ext . ' -r "exit(extension_loaded(\'xdebug\')?0:1);"');
        if($xdebugLoaded === 0)
        {
          $phpCommand .= $ext;
        }
      }
      if($xdebugLoaded === 0)
      {
        $v3 = $this->_runCommand(
          $phpCommand . ' -r "exit(version_compare(phpversion(\'xdebug\'), \'3.0.0\', \'>=\')?0:1);"'
        );
        if($v3 === 0)
        {
          $phpCommand .= ' -d xdebug.mode=debug';
          $phpCommand .= ' -d xdebug.start_with_request=1';
          $phpCommand .= ' -d xdebug.discover_client_host=1';
        }
        else
        {
          $phpCommand .= ' -d xdebug.remote_enable=1';
          $phpCommand .= ' -d xdebug.remote_autostart=1';
          $phpCommand .= ' -d xdebug.remote_connect_back=1';
        }
        $phpCommand .= ' -d xdebug.idekey=' . $this->debugIdeKey;
      }
      else
      {
        $output->writeln(['', "\tXDebug extension not installed", ""]);
      }
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
