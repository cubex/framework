<?php
namespace CubexTest\Cubex\Console\Commands;

use Cubex\Console\Commands\BuiltInWebServer;
use CubexTest\Cubex\Console\CommandTestCase;

class BuiltInWebServerTest extends CommandTestCase
{
  /**
   * @dataProvider optionsProvider
   *
   * @param array $options
   * @param       $passthru
   * @param bool  $negate
   */
  public function testCommand(array $options, $passthru, $negate = false)
  {
    $command = new TestableBuiltInWebServer();
    $this->assertEquals('serve', $command->getName());
    $bufferOut = $this->getCommandOutput($command, $options);
    if($negate)
    {
      $this->assertNotContains($passthru, $bufferOut);
    }
    else
    {
      $this->assertContains($passthru, $bufferOut);
    }
  }

  public function optionsProvider()
  {
    $pre = 'Raw Command: php ';
    $debugCommand = '-d xdebug.remote_enable=1 -d xdebug.remote_autostart=1 -d xdebug.remote_connect_back=1 -d xdebug.idekey=';
    return [
      [[], $pre . '-S 0.0.0.0:8080 -t public/index.php'],
      [[], '|__'],
      [['--showfig' => 'false'], '|__', true],
      [['--port' => '8090'], $pre . '-S 0.0.0.0:8090 -t public/index.php'],
      [['--host' => 'localhost'], $pre . '-S localhost:8080 -t public/index.php'],
      [['--router' => 'index.exec'], $pre . '-S 0.0.0.0:8080 -t index.exec'],
      [['-d' => true], $pre . $debugCommand . 'PHPSTORM -S 0.0.0.0:8080 -t public/index.php'],
      [['-d' => true, '-idekey' => 'TEST'], $pre . $debugCommand . 'TEST -S 0.0.0.0:8080 -t public/index.php'],
    ];
  }
}

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
