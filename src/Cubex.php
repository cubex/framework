<?php
namespace Cubex;

use Composer\Autoload\ClassLoader;
use Cubex\Console\Console;
use Cubex\Container\DependencyInjector;
use Cubex\Context\Context;
use Cubex\Http\ExceptionHandler;
use Cubex\Http\Handler;
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
  const EVENT_HANDLE_PRE_EXECUTE = 'handle.pre.execute';
  const EVENT_HANDLE_RESPONSE_PREPARE = 'handle.response.prepare';
  const EVENT_HANDLE_RESPONSE_PREPARED = 'handle.response.prepared';
  const EVENT_HANDLE_RESPONSE_PRE_SEND_HEADERS = 'handle.response.send.headers';
  const EVENT_HANDLE_RESPONSE_PRE_SEND_CONTENT = 'handle.response.send.content';
  const EVENT_HANDLE_COMPLETE = 'handle.complete';

  protected $_listeners = [];

  /** @var Cubex */
  private static $_cubex;
  protected $_logger;

  /**
   * @var callable
   */
  private $_projectRoot;

  public function __construct($projectRoot, ClassLoader $loader = null, $global = true)
  {
    $this->_projectRoot = $projectRoot;
    //Setup Context
    $this->share(ClassLoader::class, $loader);
    $this->factory(Context::class, $this->_defaultContextFactory());

    if($global && self::$_cubex === null)
    {
      self::$_cubex = $this;
    }
  }

  protected function _defaultContextFactory()
  {
    return function () { return $this->prepareContext(new Context(Request::createFromGlobals())); };
  }

  public function prepareContext(Context $ctx): Context
  {
    $ctx->setCubex($this);

    if($this->_projectRoot !== null)
    {
      $ctx->setProjectRoot($this->_projectRoot);
      $config = new IniConfigProvider();
      $config->loadFiles(
        [
          Path::system($ctx->getProjectRoot(), "conf", "defaults.ini"),
          Path::system($ctx->getProjectRoot(), "conf", "defaults", "config.ini"),
          Path::system($ctx->getProjectRoot(), "conf", $ctx->getEnvironment() . ".ini"),
          Path::system($ctx->getProjectRoot(), "conf", $ctx->getEnvironment(), "config.ini"),
        ],
        true,
        false
      );
      $ctx->setConfig($config);
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
      $ctx = $this->_defaultContextFactory()();
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
   * @param Handler $handler
   * @param bool    $sendResponse
   *
   * @param bool    $catchExceptions
   * @param bool    $flushHeaders
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(
    Handler $handler, $sendResponse = true, $catchExceptions = true, $flushHeaders = true
  )
  {
    $c = $this->getContext();
    try
    {
      $this->_triggerEvent(self::EVENT_HANDLE_PRE_EXECUTE, $handler);
      $r = $handler->handle($c);
      $this->_triggerEvent(self::EVENT_HANDLE_RESPONSE_PREPARE, $r);
      $r->prepare($c->request());
      $this->_triggerEvent(self::EVENT_HANDLE_RESPONSE_PREPARED, $r);
    }
    catch(\Throwable $e)
    {
      if(!$catchExceptions)
      {
        throw $e;
      }
      $this->getLogger()->error($e->getMessage());
      $r = (new ExceptionHandler($e))->handle($c);
      $r->prepare($c->request());
    }
    if($sendResponse)
    {
      $this->_triggerEvent(self::EVENT_HANDLE_RESPONSE_PRE_SEND_HEADERS, $r);
      $r->sendHeaders();
      if($flushHeaders)
      {
        ob_flush();
        flush();
      }
      $this->_triggerEvent(self::EVENT_HANDLE_RESPONSE_PRE_SEND_CONTENT, $r);
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
