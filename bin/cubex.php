#!/usr/bin/env php
<?php
/**
 * Cubex Console Application
 */

//Defining PHP_START will allow cubex to add an execution time header
define('PHP_START', microtime(true));

//Include the composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

//Create an instance of cubex, with the bin root defined
$app = new \Cubex\Cubex(__DIR__);
$app->boot();

//Create a request object
$app->instance('request', \Cubex\Http\Request::createConsoleRequest());

$console = new \Cubex\Console\Console('Cubex Console', "1.0");
$console->setCubex($app);

//Execute the command and retrieve the exit code
$exit = $console->run();

exit($exit);
