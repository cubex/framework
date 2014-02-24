<?php
use Cubex\Cubex;
use Cubex\Facade\Log;

class LogTest extends \PHPUnit_Framework_TestCase
{
  public function testAvailablePsrLevels()
  {
    $this->assertContains(\Psr\Log\LogLevel::EMERGENCY, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::ALERT, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::CRITICAL, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::ERROR, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::WARNING, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::NOTICE, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::INFO, Log::$logLevels);
    $this->assertContains(\Psr\Log\LogLevel::DEBUG, Log::$logLevels);
  }

  public function testLogger()
  {
    $cubex      = Log::getFacadeApplication();
    $logService = $cubex->make('log');
    $this->assertInstanceOf(
      '\Cubex\ServiceManager\Services\LogService',
      $logService
    );

    /**
     * @var \Cubex\ServiceManager\Services\LogService $logService
     */
    if($logService instanceof \Cubex\ServiceManager\Services\LogService)
    {
      $this->assertInstanceOf(
        'Psr\Log\LoggerInterface',
        $logService->getLogger()
      );
    }
  }

  public function testStatics()
  {
    $cubex       = Log::getFacadeApplication();
    $logger      = new TestLogger();
    $oldInstance = $logService = null;
    if($cubex instanceof Cubex)
    {
      $logService = $cubex->make('log');
      /**
       * @var $logService \Cubex\ServiceManager\Services\LogService
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

class TestLogger implements \Psr\Log\LoggerInterface
{
  public $level;
  public $message;
  public $context;

  public function alert(
    $message, array $context = array()
  )
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function critical(
    $message, array $context = array()
  )
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function emergency(
    $message, array $context = array()
  )
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function error(
    $message, array $context = array()
  )
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function warning(
    $message, array $context = array()
  )
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function notice(
    $message, array $context = array()
  )
  {
    $this->__call(__FUNCTION__, func_get_args());
  }

  public function debug(
    $message, array $context = array()
  )
  {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function info(
    $message, array $context = array()
  )
  {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function log(
    $level, $message, array $context = array()
  )
  {
    return $this->__call(__FUNCTION__, func_get_args());
  }

  public function __call($method, $args)
  {
    if($method === 'log')
    {
      $this->level   = $args[0];
      $this->message = $args[1];
      $this->context = $args[2];
    }
    else
    {
      $this->level   = $method;
      $this->message = $args[0];
      $this->context = $args[1];
    }
  }
}
