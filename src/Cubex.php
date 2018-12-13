<?php
namespace Cubex;

use Composer\Autoload\ClassLoader;
use Cubex\Console\Console;
use Cubex\Container\DependencyInjector;
use Cubex\Context\Context;
use Cubex\Http\ExceptionHandler;
use Cubex\Http\Handler;
use Cubex\Routing\Router;
use Exception;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Packaged\Helpers\Path;
use Packaged\Http\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class Cubex extends DependencyInjector implements LoggerAwareInterface
{
  const EVENT_CONSOLE_CREATE = 'console.create';
  const EVENT_CONSOLE_PREPARE = 'console.pre.run';
  const EVENT_HANDLE_START = 'handle.start';
  const EVENT_HANDLE_PRE_EXECUTE = 'handle.pre.execute';
  const EVENT_HANDLE_RESPONSE_PREPARE = 'handle.response.prepare';
  const EVENT_HANDLE_COMPLETE = 'handle.complete';

  const ERROR_NO_HANDLER = 'No handler was available to process your request';

  protected $_listeners = [];

  /** @var Cubex */
  private static $_cubex;
  protected $_logger;

  public function __construct($projectRoot, ClassLoader $loader = null, $global = true)
  {
    //Setup Context
    $this->share(ClassLoader::class, $loader);
    $this->share(Context::class, $this->_newContext($projectRoot));

    if($global && self::$_cubex === null)
    {
      self::$_cubex = $this;
    }
  }

  protected function _newContext($projectRoot = null)
  {
    $ctx = new Context(Request::createFromGlobals());
    $ctx->setCubex($this);

    if($projectRoot !== null)
    {
      $ctx->setProjectRoot($projectRoot);
      try
      {
        $ctx->setConfig(new IniConfigProvider(Path::system($ctx->getProjectRoot(), "conf", "defaults.ini")));
      }
      catch(\Throwable $e)
      {
        //If no config file exists, thats fine
      }
    }
    return $ctx;
  }

  public function getContext(): Context
  {
    try
    {
      $ctx = $this->retrieve(Context::class);
    }
    catch(Exception $e)
    {
      $ctx = $this->_newContext();
    }
    return $ctx;
  }

  protected $_console;

  /**
   * @return Console
   */
  public function getConsole()
  {
    if(!$this->_console)
    {
      $this->_console = new Console("Cubex Console", "3.0");
      $this->_console->setAutoExit(false);
      $this->_console->setContext($this->getContext());
      $this->_triggerEvent(self::EVENT_CONSOLE_CREATE, $this->_console);
    }
    return $this->_console;
  }

  /**
   * @param InputInterface|null  $input
   * @param OutputInterface|null $output
   *
   * @return int
   * @throws Exception
   */
  public function cli(InputInterface $input = null, OutputInterface $output = null)
  {
    $input = $input ?? new ArgvInput();
    $output = $output ?? new ConsoleOutput();

    $console = $this->getConsole();
    $this->_triggerEvent(self::EVENT_CONSOLE_PREPARE, $console, $input, $output);

    try
    {
      $exitCode = $console->run($input, $output);
    }
    catch(\Throwable $e)
    {
      $output->writeln("GENERIC EXCEPTION : " . $e->getCode());
      $output->writeln($e->getMessage());
      $exitCode = 1;
    }

    return $exitCode > 255 ? 255 : $exitCode;
  }

  /**
   * @param Router $router
   * @param bool   $catchExceptions
   * @param bool   $sendResponse
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(Router $router, $sendResponse = true, $catchExceptions = true)
  {
    $c = $this->getContext();
    try
    {
      $this->_triggerEvent(self::EVENT_HANDLE_START);
      $handler = $router->getHandler($c);
      if($handler === null || !($handler instanceof Handler))
      {
        throw new \RuntimeException(self::ERROR_NO_HANDLER, 500);
      }
      $this->_triggerEvent(self::EVENT_HANDLE_PRE_EXECUTE, $handler);
      $r = $handler->handle($c);
      $this->_triggerEvent(self::EVENT_HANDLE_RESPONSE_PREPARE, $r);
      $r->prepare($c->getRequest());
    }
    catch(\Throwable $e)
    {
      if(!$catchExceptions)
      {
        throw $e;
      }
      $this->getLogger()->error($e->getMessage());
      $r = (new ExceptionHandler($e))->handle($c);
      $r->prepare($c->getRequest());
    }
    if($sendResponse)
    {
      $r->send();
    }
    try
    {
      $this->_triggerEvent(self::EVENT_HANDLE_COMPLETE, $r);
    }
    catch(\Throwable $e)
    {
      if(!$catchExceptions)
      {
        throw $e;
      }
      $this->getLogger()->error($e->getMessage());
    }
    return $r;
  }

  /**
   * Listen to a Cubex Event
   *
   * @param          $eventAlias
   * @param callable $callback
   *
   * @return $this
   */
  public function listen($eventAlias, callable $callback)
  {
    if(!isset($this->_listeners[$eventAlias]))
    {
      $this->_listeners[$eventAlias] = [];
    }
    $this->_listeners[$eventAlias][] = $callback;
    return $this;
  }

  protected function _triggerEvent($eventAlias, ...$data)
  {
    if(isset($this->_listeners[$eventAlias]))
    {
      foreach($this->_listeners[$eventAlias] as $callback)
      {
        $callback($this->getContext(), ...$data);
      }
    }
  }

  /**
   * @param LoggerInterface $logger
   *
   * @return void
   */
  public function setLogger(LoggerInterface $logger)
  {
    $this->share(LoggerInterface::class, $logger);
  }

  /**
   * @return LoggerInterface
   */
  public function getLogger()
  {
    try
    {
      $logger = $this->retrieve(LoggerInterface::class);
    }
    catch(Exception $e)
    {
      $logger = new NullLogger();
    }
    return $logger;
  }

  /**
   * @return LoggerInterface
   */
  public static function log()
  {
    return self::$_cubex->getLogger();
  }

  /**
   * @return Cubex|null
   */
  public static function instance()
  {
    return self::$_cubex;
  }

  public static function destroyGlobalInstance()
  {
    self::$_cubex = null;
  }
}
