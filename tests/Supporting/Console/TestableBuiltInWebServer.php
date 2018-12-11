<?php
namespace Cubex\Tests\Supporting\Console;

use Cubex\Console\Commands\BuiltInWebServer;

class TestableBuiltInWebServer extends BuiltInWebServer
{
  public function __construct($name = null)
  {
    parent::__construct($name);
    $this->_executeMethod = [$this, '_commander'];
  }

  protected function _commander($command, &$code)
  {
    $code = 0;
    return $command;
  }
}
