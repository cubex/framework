<?php
//Include the composer autoloader
require_once dirname(
    dirname(dirname(dirname(__DIR__)))
  ) . '/vendor/autoload.php';

//Create an instance of cubex, with the web root defined
$app = new \Cubex\Cubex(dirname(dirname(dirname(__DIR__))));
$app->setEnv('phpunit');
$app->boot();

//Bootstrap Cubex Test Cases
$startup = new \Cubex\Testing\Bootstrap($app);
$startup->boot();
