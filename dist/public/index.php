<?php
define('PHP_START', microtime(true));

use Cubex\CubexLauncher;
use Cubex\Routing\Router;
use Project\DefaultHandler;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$router = new Router();
$router->handle("/", new DefaultHandler());

//Handle the request
$launcher = new CubexLauncher(dirname(__DIR__));
$launcher->handleWithRouter($router, true);
$launcher->shutdown();
