<?php
namespace Cubex\Console;

use Cubex\Console\Commands\BuiltInWebServer;
use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Packaged\Config\ConfigProviderInterface;
use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_merge;
use function class_exists;
use function explode;
use function implode;
use function is_int;
use function str_replace;
use function ucwords;

class Console extends Application implements ContextAware
{
  protected $_searchPatterns = ['%s'];
  protected $_configured = false;

  use ContextAwareTrait;
  use CubexAwareTrait;

  /**
   * Runs the current application.
   *
   * @param InputInterface  $input  An Input instance
   * @param OutputInterface $output An Output instance
   *
   * @return integer 0 if everything went fine, or an error code
   * @throws \Throwable
   */
  public function doRun(InputInterface $input, OutputInterface $output)
  {
    $this->configure($this->getContext()->config());
    return parent::doRun($input, $output);
  }

  /**
   * Pull the configuration from cubex and setup resolving patterns and
   * defined command lists
   *
   * @param ConfigProviderInterface $cfg
   *
   * @return $this
   */
  public function configure(ConfigProviderInterface $cfg)
  {
    if($this->_configured)
    {
      return $this;
    }

    try
    {
      $config = $cfg->getSection('console');
      $commands = $config->getItem('commands', []);
      $patterns = $config->getItem('patterns', []);
    }
    catch(\Throwable $e)
    {
      $commands = $patterns = [];
    }

    $this->_searchPatterns = array_merge($this->_searchPatterns, $patterns);

    foreach($commands as $name => $class)
    {
      $command = $this->_getCommandByString($class);
      if($command !== null)
      {
        if(!is_int($name))
        {
          $command->setName($name);
        }
        $this->add($command);
      }
    }

    $this->_configured = true;
    return $this;
  }

  /**
   * @param string $name    Name of the command
   * @param bool   $setName If the name should be passed through from the call
   *
   * @return Command|null
   */
  protected function _getCommandByString($name, $setName = false)
  {
    if(strpos($name, '.') !== false)
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

      $command = $setName ? new $attempt($name) : new $attempt();
      if($command instanceof Command)
      {
        return $this->_prepareCommand($command);
      }
    }

    return null;
  }

  protected function _prepareCommand(Command $command): Command
  {
    if($this->hasContext() && $command instanceof ContextAware)
    {
      $command->setContext($this->getContext());
    }

    if($this->hasCubex() && $command instanceof CubexAware)
    {
      $command->setCubex($this->getCubex());
    }
    return $command;
  }

  /**
   * Find a command, and fail over to namespaced class split on .
   *
   * @param string $name
   *
   * @return Command
   * @throws \Throwable
   */
  public function find($name)
  {
    try
    {
      return parent::find($name);
    }
    catch(\Throwable $e)
    {
      $command = $this->_getCommandByString($name, true);
      if($command !== null)
      {
        $this->add($command);
        return $this->get($name);
      }
      throw $e;
    }
  }

  /**
   * @return array|Command[]
   * @throws \ReflectionException
   */
  protected function getDefaultCommands()
  {
    $commands = parent::getDefaultCommands();
    $commands[] = $this->_prepareCommand(new BuiltInWebServer());
    return $commands;
  }
}
