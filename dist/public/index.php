<?php
define('PHP_START', microtime(true));

use Cubex\Cubex;
use Cubex\Routing\Router;
use Project\DefaultHandler;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$launcher = new Cubex(dirname(__DIR__));
try
{
  $router = Router::i();
  $router->handle("/", new DefaultHandler());
  $launcher->handle($router);
}
catch(Throwable $e)
{
  die("Your request could not be handled");
}
