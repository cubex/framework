<?php
namespace CubexTest\Cubex\Console;

use Cubex\Console\Commands\BuiltInWebServer;
use Cubex\Console\Console;
use Cubex\Cubex;
use Packaged\Config\Provider\Test\TestConfigProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleTest extends \PHPUnit_Framework_TestCase
{
  public function getConsole(InputInterface $input, OutputInterface $output)
  {
    $cubex = new Cubex();
    $console = Console::withCubex($cubex);
    $config = new TestConfigProvider();
    $config->addItem(
      'console',
      'commands',
      [
        '\namespaced\NamerCommand',
        'phpserver' => 'CubexTest\Cubex\Console\PhpWebServer',
        'broken'    => 'InvalidClass',
      ]
    );
    $config->addItem(
      'console',
      'patterns',
      [
        '\namespaced\sub\%s',
      ]
    );

    $cubex->configure($config);
    $console->setCubex($cubex);
    $console->configure($input, $output);
    return $console;
  }

  public function testDoRun()
  {
    $output = new BufferedOutput();
    $input = new ArrayInput([]);

    $console = $this->getConsole($input, $output);
    $console->doRun($input, $output);
    $buffered = $output->fetch();

    $this->assertContains('phpserver', $buffered);
    $this->assertContains('Namer', $buffered);
    $this->assertContains(
      'Command [broken] does not reference a valid class',
      $buffered
    );
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
    $output = new BufferedOutput();
    $input = new ArrayInput([]);

    $console = $this->getConsole($input, $output);
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
