<?php

class BuiltInWebServerTest extends CommandTestCase
{
  /**
   * @dataProvider optionsProvider
   *
   * @param array $options
   * @param       $passthru
   */
  public function testCommand(array $options, $passthru)
  {
    $command = new TestableBuiltInWebServer();
    $this->assertEquals('serve', $command->getName());
    $bufferOut = $this->getCommandOutput($command, $options);
    $this->assertContains('Raw Command: php -S ' . $passthru, $bufferOut);
  }

  public function optionsProvider()
  {
    return [
      [[], '0.0.0.0:8080 -t public/index.php'],
      [['--port' => '8090'], '0.0.0.0:8090 -t public/index.php'],
      [['--host' => 'localhost'], 'localhost:8080 -t public/index.php'],
      [['--router' => 'index.exec'], '0.0.0.0:8080 -t index.exec'],
    ];
  }
}

class TestableBuiltInWebServer extends \Cubex\Console\Commands\BuiltInWebServer
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
