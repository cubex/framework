<?php
namespace Cubex\Console;

use Cubex\Console\Commands\BuiltInWebServer;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends Application implements ContextAware
{
  protected $_searchPatterns = ['%s'];
  protected $_configured = false;

  use ContextAwareTrait;

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
    $this->configure($input, $output);
    return parent::doRun($input, $output);
  }

  /**
   * Pull the configuration from cubex and setup resolving patterns and
   * defined command lists
   *
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return $this
   */
  public function configure(InputInterface $input, OutputInterface $output)
  {
    if($this->_configured)
    {
      return $this;
    }

    try
    {
      $config = $this->getContext()->config()->getSection('console');
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
        else
        {
          $output->writeln(
            '<error>Command [' . $name . '] does not reference a valid class</error>'
          );
        }
      }
    }
    catch(\Throwable $e)
    {
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
  protected function getCommandByString($name, $setName = false)
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

      $command = $setName ? new $attempt($name) : new $attempt();
      if($command instanceof Command)
      {
        return $this->_prepareCommand($command);
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
      $command = $this->getCommandByString($name, true);
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

  protected function _prepareCommand(Command $command): Command
  {
    if($this->hasContext() && $command instanceof ContextAware)
    {
      $command->setContext($this->getContext());
    }
    return $command;
  }
}
