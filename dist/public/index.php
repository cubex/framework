<?php
define('PHP_START', microtime(true));

use Cubex\CubexLauncher;
use Cubex\Routing\Router;
use Project\DefaultHandler;

require_once(dirname(__DIR__) . '/vendor/autoload.php');

$launcher = new CubexLauncher(dirname(__DIR__));
$launcher->handle(Router::i()->handle("/", new DefaultHandler()), true);
