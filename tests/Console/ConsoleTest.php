<?php
namespace Cubex\Tests\Console;

use Cubex\Cubex;
use Cubex\Tests\Supporting\Console\TestConsoleCommand;
use Cubex\Tests\Supporting\Console\TestExceptionCommand;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Config\Provider\Test\TestConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleTest extends TestCase
{
  /**
   * @throws \Exception
   */
  public function testInvalidCommand()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $output = new BufferedOutput();
    $input = new ArrayInput(['broken']);
    $cubex->cli($input, $output);
    $this->assertContains('Command "broken" is not defined', $output->fetch());
  }

  /**
   * @throws \Throwable
   */
  public function testConfigure()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $console = $cubex->getConsole();
    $cfg = new TestConfigProvider();
    $section = new ConfigSection(
      'console', [
        'patterns' => [
          'Cubex\Tests\Supporting\Console\%s',
        ],
        'commands' => [
          'Tester' => TestConsoleCommand::class,
        ],
      ]
    );
    $cfg->addSection($section);
    $console->configure($cfg);
    //Ensure config does not get re-run
    $console->configure(new TestConfigProvider());
    $this->assertInstanceOf(TestExceptionCommand::class, $console->find('TestExceptionCommand'));
    $this->assertInstanceOf(TestConsoleCommand::class, $console->find('Tester'));
  }

  /**
   * @param      $instance
   * @param      $string
   * @param bool $exception
   *
   * @throws \Exception
   * @throws \Throwable
   *
   * @dataProvider findProvider
   */
  public function testFind($instance, $string, $exception = false)
  {
    $cubex = new Cubex(__DIR__, null, false);
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
    $config->addItem('console', 'patterns', ['\namespaced\sub\%s',]);
    $cubex->getContext()->setConfig($config);
    $console = $cubex->getConsole();
    if($exception)
    {
      $this->expectException('InvalidArgumentException');
    }
    $this->assertInstanceOf($instance, $console->find($string));
  }

  public function findProvider()
  {
    return [
      ['\Cubex\Console\Commands\BuiltInWebServer', 'serve'],
      [null, 'missing.service-x', true],
      [TestExceptionCommand::class, 'Cubex.Tests.Supporting.Console.TestExceptionCommand'],
      ['\namespaced\TheRoutable', 'namespaced.TheRoutable', true],
    ];
  }
}
