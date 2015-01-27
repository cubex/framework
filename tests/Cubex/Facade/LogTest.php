<?php
namespace CubexTest\Cubex\Facade;

use Cubex\Cubex;
use Cubex\Facade\Log;
use Cubex\ServiceManager\Services\LogService;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogTest extends \PHPUnit_Framework_TestCase
{
  public function testAvailablePsrLevels()
  {
    $this->assertContains(LogLevel::EMERGENCY, Log::$logLevels);
    $this->assertContains(LogLevel::ALERT, Log::$logLevels);
    $this->assertContains(LogLevel::CRITICAL, Log::$logLevels);
    $this->assertContains(LogLevel::ERROR, Log::$logLevels);
    $this->assertContains(LogLevel::WARNING, Log::$logLevels);
    $this->assertContains(LogLevel::NOTICE, Log::$logLevels);
    $this->assertContains(LogLevel::INFO, Log::$logLevels);
    $this->assertContains(LogLevel::DEBUG, Log::$logLevels);
  }

  public function testLogger()
  {
    $cubex = Log::getFacadeApplication();
    $logService = $cubex->make('log');
    $this->assertInstanceOf(
      '\Cubex\ServiceManager\Services\LogService',
      $logService
    );

    /**
     * @var LogService $logService
     */
    if($logService instanceof LogService)
    {
      $this->assertInstanceOf(
        'Psr\Log\LoggerInterface',
        $logService->getLogger()
      );
    }
  }

  public function testStatics()
  {
    $cubex = Log::getFacadeApplication();
    $logger = new TestLogger();
    $oldInstance = $logService = null;
    if($cubex instanceof Cubex)
    {
      $logService = $cubex->make('log');
      /**
       * @var $logService LogService
       */
      $oldInstance = $logService->getLogger();
      $logService->setLogger($logger);
    }

    $message = rand(10, 2323);
    $context = ['now' => microtime(true)];

    foreach(Log::$logLevels as $level)
    {
      Log::$level($message, $context);
      $this->assertEquals($level, $logger->level);
      $this->assertEquals($message, $logger->message);
      $this->assertEquals($context, $logger->context);
    }

    Log::custom('tester', $message, $context);
    $this->assertEquals('tester', $logger->level);
    $this->assertEquals($message, $logger->message);
    $this->assertEquals($context, $logger->context);

    if($logService !== null && $oldInstance !== null)
    {
      $logService->setLogger($oldInstance);
    }
  }

  /**
   * @dataProvider logLevelAllowProvider
   *
   * @param $messageLevel
   * @param $logLevel
   * @param $expect
   */
  public function testLogLevelAllowed($messageLevel, $logLevel, $expect)
  {
    $this->assertEquals(
      $expect,
      Log::logLevelAllowed($messageLevel, $logLevel)
    );
  }

  public function logLevelAllowProvider()
  {
    return [
      ['info', 'error', false],
      ['error', 'info', true],
    ];
  }
}

class TestLogger implements LoggerInterface
{
  public $level;
  public $message;
  public $context;

  public function alert($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function critical($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function emergency($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function error($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function warning($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function notice($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function debug($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function info($message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function log($level, $message, array $context = [])
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function __call($method, $args)
  {
    if($method === 'log')
    {
      $this->level = $args[0];
      $this->message = $args[1];
      $this->context = $args[2];
    }
    else
    {
      $this->level = $method;
      $this->message = $args[0];
      $this->context = $args[1];
    }
  }
}
