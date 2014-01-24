<?php
/**
 * This file is to be removed once versioned
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

define('PHP_START', microtime(true));

$app = new \Cubex\Cubex();

//If you wish to use stackphp, uncomment the line below
//$stack = (new \Stack\Builder())->push(middleware)->resolve($app);

$request  = \Cubex\Http\Request::createFromGlobals();
$response = $app->handle($request);
$response->send();
$app->terminate($request, $response);

echo "\n<br/>";
echo number_format((microtime(true) - PHP_START) * 1000, 3);
