<?php
namespace Cubex\Tests\Console\Commands;

use Cubex\Tests\Console\ConsoleCommandTestCase;
use Cubex\Tests\Supporting\Console\TestableBuiltInWebServer;
use Packaged\Context\Context;

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
    $ctx = new Context();
    $ctx->setProjectRoot('');
    $command->setContext($ctx);
    $this->assertEquals('serve', $command->getName());
    $bufferOut = $this->getCommandOutput($command, $options + ['--showCommand' => true]);
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
    $debugCommand = '-d zend_extension=xdebug.so -d xdebug.remote_enable=1 -d xdebug.remote_autostart=1 -d xdebug.remote_connect_back=1 -d xdebug.idekey=';
    return [
      [[], $pre . '-S 127.0.0.1:8888 -t public/index.php'],
      [[], '|__'],
      [['--showfig' => 'false'], '|__', true],
      [['--port' => '8090'], $pre . '-S 127.0.0.1:8090 -t public/index.php'],
      [['--host' => '0.0.0.0'], $pre . '-S 0.0.0.0:8888 -t public/index.php'],
      [['-c' => 'framework'], $pre . '-S framework.cubex-local.com:8888 -t public/index.php'],
      [['--router' => 'index.exec'], $pre . '-S 127.0.0.1:8888 -t index.exec'],
      [['-d' => true], $pre . $debugCommand . 'PHPSTORM -S 0.0.0.0:8888 -t public/index.php'],
      [['-d' => true, '-idekey' => 'TEST'], $pre . $debugCommand . 'TEST -S 0.0.0.0:8888 -t public/index.php'],
    ];
  }

  public function testPortIncrease()
  {
    $command = new TestableBuiltInWebServer();
    $command->setContext(new Context());
    $this->assertEquals('serve', $command->getName());
    $bufferOut = $this->getCommandOutput(
      $command,
      ['--port' => '8898', '--useNextAvailablePort' => true, '-c' => 'port-taken']
    );
    $this->assertContains(":8899", $bufferOut);
  }
}
