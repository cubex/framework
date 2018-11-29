<?php
namespace Cubex;

use Cubex\Console\Console;
use Cubex\Context\Context;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Cubex\Routing\Router;
use Exception;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Packaged\Helpers\Path;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class CubexLauncher
{
  /**
   * @var Context
   */
  private $_context;
  /**
   * @var Request
   */
  private $_request;
  /**
   * @var Response
   */
  private $_response;
  /**
   * @var Router
   */
  private $_router;

  public function __construct($projectRoot)
  {
    $this->_context = new Context();
    $this->_context->setProjectRoot($projectRoot);
    $this->configure();

    if($this->_context->isCli())
    {
      $this->_request = Request::createConsoleRequest();
    }
    else
    {
      $this->_request = Request::createFromGlobals();
    }
    $this->_response = new Response();
  }

  protected function configure()
  {
    try
    {
      $cfg = new IniConfigProvider(Path::build($this->getContext()->getProjectRoot(), "conf", "defaults.ini"));
      $this->getContext()->setConfig($cfg);
    }
    catch(Exception $e)
    {
    }
  }

  public function getContext()
  {
    return $this->_context;
  }

  public function getRequest()
  {
    return $this->_request;
  }

  public function getResponse()
  {
    return $this->_response;
  }

  public function shutdown()
  {
    //Shutdown Everything Cleanly
  }

  /**
   * @return Router
   */
  public function getRouter(): Router
  {
    return $this->_router;
  }

  public function setRouter(Router $router)
  {
    $this->_router = $router;
    return $this;
  }

  public function handleException(Exception $e)
  {
    $this->getResponse()->setStatusCode($e->getCode() >= 400 ? $e->getCode() : 500, $e->getMessage());
    $this->getResponse()->setContent($e->getMessage())->prepare($this->getRequest())->send();
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
   * @param bool $catch Catch exceptions and generate a default response
   *
   * @param bool $send
   *
   * @return $this
   * @throws Exception
   */
  public function handle($catch = true, $send = true)
  {
    return $this->handleWithRouter($this->getRouter(), $catch, $send);
  }

  /**
   * @param Router $router
   * @param bool   $catch
   * @param bool   $send
   *
   * @return $this
   * @throws ?Exception
   */
  public function handleWithRouter(Router $router, $catch = true, $send = true)
  {
    try
    {
      $handler = $router->getHandler($this->getRequest());
      if($handler === null)
      {
        throw new \RuntimeException("No handler was available to process your request");
      }
      $handler->handle($this->getContext(), $this->getResponse(), $this->getRequest());

      $this->getResponse()->prepare($this->getRequest());
      if($send)
      {
        $this->getResponse()->send();
      }
    }
    catch(Exception $e)
    {
      if(!$catch)
      {
        throw $e;
      }
      $this->handleException($e);
    }

    return $this;
  }
}
