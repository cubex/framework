<?php
namespace Cubex\Tests\Console\Commands;

use Cubex\Context\Context;
use Cubex\Tests\Console\ConsoleCommandTestCase;
use Cubex\Tests\Supporting\Console\TestableBuiltInWebServer;

class BuiltInWebServerTest extends ConsoleCommandTestCase
{
  /**
   * @dataProvider optionsProvider
   *
   * @param array $options
   * @param       $passthru
   * @param bool  $negate
   *
   * @throws \Exception
   */
  public function testCommand(array $options, $passthru, $negate = false)
  {
    $command = new TestableBuiltInWebServer();
    $command->setContext(new Context());
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
      [[], $pre . '-S 0.0.0.0:8888 -t public/index.php'],
      [[], '|__'],
      [['--showfig' => 'false'], '|__', true],
      [['--port' => '8090'], $pre . '-S 0.0.0.0:8090 -t public/index.php'],
      [['--host' => 'localhost'], $pre . '-S localhost:8888 -t public/index.php'],
      [['--router' => 'index.exec'], $pre . '-S 0.0.0.0:8888 -t index.exec'],
      [['-d' => true], $pre . $debugCommand . 'PHPSTORM -S 0.0.0.0:8888 -t public/index.php'],
      [['-d' => true, '-idekey' => 'TEST'], $pre . $debugCommand . 'TEST -S 0.0.0.0:8888 -t public/index.php'],
    ];
  }
}
