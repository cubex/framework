<?php
namespace Cubex;

use Cubex\Console\Console;
use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Cubex\Routing\Router;
use Exception;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Packaged\Helpers\Path;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CubexLauncher implements ContextAware
{
  use ContextAwareTrait;

  public function __construct($projectRoot)
  {
    $ctx = new Context();
    $this->setContext($ctx);
    $ctx->setProjectRoot($projectRoot);
    $this->configure($ctx);
  }

  protected function configure(Context $ctx)
  {
    try
    {
      $ctx->setConfig(new IniConfigProvider(Path::build($ctx->getProjectRoot(), "conf", "defaults.ini")));
    }
    catch(Exception $e)
    {
    }
  }

  public function handleException(Exception $e, Response $w, Request $r)
  {
    $w
      ->setStatusCode($e->getCode() >= 400 ? $e->getCode() : 500, $e->getMessage())
      ->setContent($e->getMessage())
      ->prepare($r)
      ->send();
  }

  public function cli()
  {
    $console = new Console("Cubex Console", "3.0");
    $console->setContext($this->getContext());
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
   * @param bool   $catch
   * @param bool   $send
   *
   * @return Response
   * @throws ?Exception
   */
  public function handle(Router $router, $catch = true, $send = true)
  {
    $r = Request::createFromGlobals();
    $w = new Response();
    try
    {
      $handler = $router->getHandler($r);
      if($handler === null)
      {
        throw new \RuntimeException("No handler was available to process your request");
      }
      $handler->handle($this->getContext(), $w, $r);

      $w->prepare($r);
      if($send)
      {
        $w->send();
      }
    }
    catch(Exception $e)
    {
      if(!$catch)
      {
        throw $e;
      }
      $this->handleException($e, $w, $r);
    }

    return $w;
  }
}
