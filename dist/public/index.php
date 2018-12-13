<?php
define('PHP_START', microtime(true));

use Cubex\Cubex;
use Cubex\Routing\Router;
use Project\DefaultHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

$loader = require_once(dirname(__DIR__) . '/vendor/autoload.php');
$launcher = new Cubex(dirname(__DIR__), $loader);
//$launcher->listen(Cubex::EVENT_HANDLE_START, function (Context $ctx) { /* Configure your request here  */ });
try
{
  $router = Router::i();
  $router->handle("/", new DefaultHandler());
  $launcher->handle($router);
}
catch(Throwable $e)
{
  $handler = new Run();
  $handler->pushHandler(new PrettyPageHandler());
  $handler->handleException($e);
}
