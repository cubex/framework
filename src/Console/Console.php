<?php
namespace Cubex\Console;

use Cubex\Console\Commands\BuiltInWebServer;
use Cubex\CubexAwareTrait;
use Cubex\ICubexAware;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends Application implements ICubexAware
{
  use CubexAwareTrait;

  protected $_searchPatterns = ['%s'];
  protected $_configured = false;

  /**
   * Constructor.
   *
   * @param string $name    The name of the application
   * @param string $version The version of the application
   *
   * @api
   */
  public function __construct($name = 'Cubex Console', $version = 'UNKNOWN')
  {
    parent::__construct($name, $version);
  }

  /**
   * Runs the current application.
   *
   * @param InputInterface  $input  An Input instance
   * @param OutputInterface $output An Output instance
   *
   * @return integer 0 if everything went fine, or an error code
   */
  public function doRun(InputInterface $input, OutputInterface $output)
  {
    $this->configure();
    return parent::doRun($input, $output);
  }

  public function configure()
  {
    if($this->_configured)
    {
      return $this;
    }

    try
    {
      $config   = $this->getCubex()->getConfiguration()->getSection('console');
      $commands = $config->getItem('commands', []);
      $patterns = $config->getItem('patterns', []);

      $this->_searchPatterns = array_merge($this->_searchPatterns, $patterns);

      foreach($commands as $name => $class)
      {
        $command = $this->getCommandByString($class);
        if($command !== null)
        {
          if(!is_int($name))
          {
            $command->setName($name);
          }
          $this->add($command);
        }
      }
    }
    catch(\Exception $e)
    {
    }
    $this->_configured = true;
    return $this;
  }

  /**
   * @param $name
   *
   * @return Command|null
   */
  protected function getCommandByString($name)
  {
    if(stristr($name, '.'))
    {
      $parts = explode(' ', ucwords(str_replace('.', ' ', $name)));
      $class = '\\' . implode('\\', $parts);
    }
    else
    {
      $class = $name;
    }

    foreach($this->_searchPatterns as $pattern)
    {
      $attempt = str_replace(['.', '%s'], ['\\', $class], $pattern);

      if(!class_exists($attempt))
      {
        continue;
      }

      $command = new $attempt($name);
      if($command instanceof Command)
      {
        return $command;
      }
    }

    return null;
  }

  /**
   * Find a command, and fail over to namespaced class split on .
   *
   * @param string $name
   *
   * @return Command
   * @throws \Exception
   */
  public function find($name)
  {
    try
    {
      return parent::find($name);
    }
    catch(\Exception $e)
    {
      $command = $this->getCommandByString($name);
      if($command !== null)
      {
        $this->add($command);
        return $this->get($name);
      }
      throw $e;
    }
  }

  /**
   * @inheritdoc
   */
  protected function getDefaultCommands()
  {
    $commands   = parent::getDefaultCommands();
    $commands[] = new BuiltInWebServer();
    return $commands;
  }
}
