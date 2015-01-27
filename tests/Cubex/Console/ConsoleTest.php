<?php
namespace CubexTest\Cubex\Console;

use Cubex\Console\Commands\BuiltInWebServer;
use Cubex\Console\Console;
use Cubex\Cubex;
use Packaged\Config\Provider\Test\TestConfigProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleTest extends \PHPUnit_Framework_TestCase
{
  public function getConsole()
  {
    $cubex = new Cubex();
    $console = Console::withCubex($cubex);
    $config = new TestConfigProvider();
    $config->addItem(
      'console',
      'commands',
      [
        '\namespaced\NamerCommand',
        'phpserver' => 'CubexTest\Cubex\Console\PhpWebServer'
      ]
    );
    $config->addItem(
      'console',
      'patterns',
      [
        '\namespaced\sub\%s'
      ]
    );

    $cubex->configure($config);
    $console->setCubex($cubex);
    $console->configure();
    return $console;
  }

  public function testDoRun()
  {
    $console = $this->getConsole();

    $output = new BufferedOutput();
    $input = new ArrayInput([]);

    $console->doRun($input, $output);
    $buffered = $output->fetch();

    $this->assertContains('phpserver', $buffered);
    $this->assertContains('Namer', $buffered);
  }

  /**
   * @param      $instance
   * @param      $string
   * @param bool $exception
   *
   * @throws \Exception
   *
   * @dataProvider findProvider
   */
  public function testFind($instance, $string, $exception = false)
  {
    $console = $this->getConsole();
    if($exception)
    {
      $this->setExpectedException('InvalidArgumentException');
    }
    $this->assertInstanceOf($instance, $console->find($string));
  }

  public function findProvider()
  {
    return [
      ['\Cubex\Console\Commands\BuiltInWebServer', 'serve'],
      [null, 'missing.service-x', true],
      ['\namespaced\NamerCommand', 'Namer'],
      ['\namespaced\TheRoutable', 'namespaced.TheRoutable', true],
      ['\namespaced\sub\HiddenCommand', 'namespaced.sub.HiddenCommand'],
    ];
  }
}

class PhpWebServer extends BuiltInWebServer
{
}
