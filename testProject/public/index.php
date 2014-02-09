<?php
/**
 * This file is to be removed once versioned
 */
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

define('PHP_START', microtime(true));

$app = new \Cubex\Cubex(__DIR__);

//If you wish to use stackphp, uncomment the line below
//$stack = (new \Stack\Builder())->push(middleware)->resolve($app);

$request  = \Cubex\Http\Request::createFromGlobals();
$response = $app->handle($request);
$response->send();
$app->terminate($request, $response);
