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
    $pre = 'Raw Command: php -S ';
    return [
      [[], $pre . '0.0.0.0:8080 -t public/index.php'],
      [[], '|__'],
      [['--showfig' => 'false'], '|__', true],
      [['--port' => '8090'], $pre . '0.0.0.0:8090 -t public/index.php'],
      [['--host' => 'localhost'], $pre . 'localhost:8080 -t public/index.php'],
      [['--router' => 'index.exec'], $pre . '0.0.0.0:8080 -t index.exec'],
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
