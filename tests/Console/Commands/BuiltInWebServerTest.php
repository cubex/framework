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
    $pre = 'Raw Command: php -S ';
    return [
      [[], $pre . '0.0.0.0:8888 -t public/index.php'],
      [[], '|__'],
      [['--showfig' => 'false'], '|__', true],
      [['--port' => '8090'], $pre . '0.0.0.0:8090 -t public/index.php'],
      [['--host' => 'localhost'], $pre . 'localhost:8888 -t public/index.php'],
      [['--router' => 'index.exec'], $pre . '0.0.0.0:8888 -t index.exec'],
    ];
  }
}
