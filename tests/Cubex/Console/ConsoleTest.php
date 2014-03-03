<?php

class ConsoleTest extends PHPUnit_Framework_TestCase
{
  public function getConsole()
  {
    $cubex   = new \Cubex\Cubex();
    $console = \Cubex\Console\Console::withCubex($cubex);
    $config  = new \Packaged\Config\Provider\Test\TestConfigProvider();
    $config->addItem(
      'console',
      'commands',
      [
        '\namespaced\NamerCommand',
        'phpserver' => 'PhpWebServer'
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

    $output = new \Symfony\Component\Console\Output\BufferedOutput();
    $input  = new \Symfony\Component\Console\Input\ArrayInput([]);

    $console->doRun($input, $output);
    $buffered = $output->fetch();

    $this->assertContains('phpserver', $buffered);
    $this->assertContains('Namer', $buffered);
  }

  /**
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
      ['\namespaced\NamerCommand', 'namespaced.NamerCommand'],
      ['\namespaced\TheRoutable', 'namespaced.TheRoutable', true],
      ['\namespaced\sub\HiddenCommand', 'HiddenCommand'],
    ];
  }
}

class PhpWebServer extends \Cubex\Console\Commands\BuiltInWebServer
{
}
