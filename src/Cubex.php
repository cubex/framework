<?php
namespace Cubex;

use Composer\Autoload\ClassLoader;
use Cubex\Console\Console;
use Cubex\Console\Events\ConsoleCreateEvent;
use Cubex\Console\Events\ConsolePrepareEvent;
use Cubex\Container\DependencyInjector;
use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Events\Handle\HandleCompleteEvent;
use Cubex\Events\Handle\ResponsePreparedEvent;
use Cubex\Events\Handle\ResponsePrepareEvent;
use Cubex\Events\Handle\ResponsePreSendContentEvent;
use Cubex\Events\Handle\ResponsePreSendHeadersEvent;
use Cubex\Events\PreExecuteEvent;
use Cubex\Http\ExceptionHandler;
use Cubex\Http\Handler;
use Exception;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Packaged\Event\Channel\Channel;
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
  const _ENV_VAR = 'CUBEX_ENV';

  /** @var Cubex */
  private static $_cubex;
  protected $_logger;
  /** @var Channel */
  protected $_eventChannel;

  /**
   * @var callable
   */
  private $_projectRoot;

  public function __construct($projectRoot, ClassLoader $loader = null, $global = true)
  {
    $this->_projectRoot = $projectRoot;
    $this->_eventChannel = new Channel('cubex');
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
    return function () {
      return $this->prepareContext(new Context(Request::createFromGlobals()));
    };
  }

  public function prepareContext(Context $ctx): Context
  {
    $ctx->setCubex($this);

    if($this->_projectRoot !== null)
    {
      $ctx->setProjectRoot($this->_projectRoot);
      $confDir = rtrim($ctx->getProjectRoot(), DIRECTORY_SEPARATOR) . "conf" . DIRECTORY_SEPARATOR;
      $config = new IniConfigProvider();
      $config->loadFiles(
        [
          $confDir . "defaults.ini",
          $confDir . "defaults" . DIRECTORY_SEPARATOR . "config.ini",
          $confDir . $ctx->getEnvironment() . ".ini",
          $confDir . $ctx->getEnvironment() . DIRECTORY_SEPARATOR . "config.ini",
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
   *
   * @throws Exception
   */
  public function getConsole()
  {
    if(!$this->_console)
    {
      $this->_console = new Console("Cubex Console", "4.0");
      $this->_console->setAutoExit(false);
      $this->_console->setContext($this->getContext());
      $this->_eventChannel->trigger(ConsoleCreateEvent::i($this->getContext(), $this->_console));
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
    $this->_eventChannel->trigger(ConsolePrepareEvent::i($this->getContext(), $console, $input, $output));

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
   * @param bool    $throwEventExceptions
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(
    Handler $handler, $sendResponse = true, $catchExceptions = true, $flushHeaders = true, $throwEventExceptions = false
  )
  {
    $this->_eventChannel->setShouldThrowExceptions($throwEventExceptions);
    $c = $this->getContext();
    if($handler instanceof ContextAware)
    {
      $handler->setContext($c);
    }

    try
    {
      $this->_eventChannel->trigger(PreExecuteEvent::i($c, $handler));
      $r = $handler->handle($c);
      $this->_eventChannel->trigger(ResponsePrepareEvent::i($c, $handler, $r));
      $r->prepare($c->request());
      $this->_eventChannel->trigger(ResponsePreparedEvent::i($c, $handler, $r));
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
    try
    {
      if($sendResponse)
      {
        $this->_eventChannel->trigger(ResponsePreSendHeadersEvent::i($c, $handler, $r));
        $r->sendHeaders();
        if($flushHeaders)
        {
          flush();
        }
        $this->_eventChannel->trigger(ResponsePreSendContentEvent::i($c, $handler, $r));
        $r->send();
      }
      $this->_eventChannel->trigger(HandleCompleteEvent::i($c, $handler, $r));
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
    $this->_eventChannel->listen($eventAlias, $callback);
    return $this;
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

  /**
   * @return string Current environment set in static::_ENV_VAR
   */
  public function getSystemEnvironment()
  {
    //Calculate the environment
    $env = getenv(static::_ENV_VAR);
    if(($env === null || !$env) && isset($_ENV[static::_ENV_VAR]))
    {
      $env = (string)$_ENV[static::_ENV_VAR];
    }
    if($env === null || !$env)//If there is no environment available, assume local
    {
      $env = Context::ENV_LOCAL;
    }
    return $env;
  }
}
