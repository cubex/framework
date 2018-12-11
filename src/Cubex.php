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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Response;

class Cubex extends DependencyInjector implements LoggerAwareInterface
{
  const EVENT_HANDLE_START = 'handle.start';
  const EVENT_HANDLE_PRE_EXECUTE = 'handle.pre.execute';
  const EVENT_HANDLE_RESPONSE_PREPARE = 'handle.response.prepare';
  const EVENT_HANDLE_COMPLETE = 'handle.complete';
  protected $_listeners = [];

  /** @var Cubex */
  private static $_cubex;
  protected $_logger;

  public function __construct($projectRoot, ClassLoader $loader = null, $global = true)
  {
    //Setup Context
    $this->setupContext($projectRoot);
    if($loader !== null)
    {
      $this->share(ClassLoader::class, $loader);
    }

    if($global && self::$_cubex === null)
    {
      self::$_cubex = $this;
    }
  }

  protected function setupContext($projectRoot)
  {
    $ctx = new Context(Request::createFromGlobals());
    $this->share(Context::class, $ctx);
    $ctx->setProjectRoot($projectRoot);
    $ctx->setCubex($this);
    try
    {
      $ctx->setConfig(new IniConfigProvider(Path::build($ctx->getProjectRoot(), "conf", "defaults.ini")));
    }
    catch(\Throwable $e)
    {
    }
  }

  /**
   * @return int
   * @throws Exception
   */
  public function cli()
  {
    $console = new Console("Cubex Console", "3.0");
    $ctx = $this->retrieve(Context::class);
    if($ctx instanceof Context)
    {
      $console->setContext($ctx);
    }
    $input = new ArgvInput();
    $output = new ConsoleOutput();

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

    return $exitCode;
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
    $c = $this->retrieve(Context::class);
    if(!($c instanceof Context))
    {
      throw new \Exception("Cubex context missing");
    }
    try
    {
      $this->_triggerEvent(self::EVENT_HANDLE_START, $c);
      $handler = $router->getHandler($c->getRequest());
      if($handler === null || !($handler instanceof Handler))
      {
        throw new \RuntimeException("No handler was available to process your request");
      }
      $this->_triggerEvent(self::EVENT_HANDLE_PRE_EXECUTE, $c, $handler);
      $r = $handler->handle($c);
      $this->_triggerEvent(self::EVENT_HANDLE_RESPONSE_PREPARE, $c, $r);
      $r->prepare($c->getRequest());
    }
    catch(\Throwable $e)
    {
      if(!$catchExceptions)
      {
        $this->getLogger()->error($e->getMessage());
        throw $e;
      }
      $r = (new ExceptionHandler($e))->handle($c);
      $r->prepare($c->getRequest());
    }
    if($sendResponse)
    {
      $r->send();
    }
    try
    {
      $this->_triggerEvent(self::EVENT_HANDLE_COMPLETE, $c, $r);
    }
    catch(\Throwable $e)
    {
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

  protected function _triggerEvent($eventAlias, Context $c, ...$data)
  {
    if(isset($this->_listeners[$eventAlias]))
    {
      foreach($this->_listeners[$eventAlias] as $callback)
      {
        $callback($c, ...$data);
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
}
