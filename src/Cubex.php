<?php
namespace Cubex;

use Cubex\Console\Console;
use Cubex\Container\DependencyInjector;
use Cubex\Context\Context;
use Cubex\Http\ExceptionHandler;
use Cubex\Http\Handler;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Cubex\Routing\Router;
use Exception;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Packaged\Helpers\Path;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class Cubex extends DependencyInjector
{
  public function __construct($projectRoot)
  {
    //Setup Context
    $this->setupContext($projectRoot);
  }

  protected function setupContext($projectRoot)
  {
    $ctx = new Context();
    $this->share(Context::class, $ctx);
    $ctx->setProjectRoot($projectRoot);
    $ctx->setCubex($this);
    try
    {
      $ctx->setConfig(new IniConfigProvider(Path::build($ctx->getProjectRoot(), "conf", "defaults.ini")));
    }
    catch(Exception $e)
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
    catch(Exception $e)
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
   * @throws Exception
   */
  public function handle(Router $router, $sendResponse = true, $catchExceptions = true)
  {
    $c = $this->retrieve(Context::class);
    if(!($c instanceof Context))
    {
      throw new \Exception("Cubex context missing");
    }
    $r = Request::createFromGlobals();
    $w = new Response();
    try
    {
      $handler = $router->getHandler($r);
      if($handler === null || !($handler instanceof Handler))
      {
        throw new \RuntimeException("No handler was available to process your request");
      }
      $handler->handle($c, $w, $r);

      $w->prepare($r);
      if($sendResponse)
      {
        $w->send();
      }
    }
    catch(Exception $e)
    {
      if(!$catchExceptions)
      {
        throw $e;
      }
      (new ExceptionHandler($e))->handle($c, $w, $r);
    }

    return $w;
  }
}
