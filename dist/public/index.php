<?php
define('PHP_START', microtime(true));

use Cubex\Cubex;
use Cubex\Routing\Router;
use Project\DefaultHandler;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$launcher = new Cubex(dirname(__DIR__));
try
{
  $launcher->handle(Router::i()->handle("/", new DefaultHandler()));
}
catch(Throwable $e)
{
  die("Your request could not be handeld");
}
