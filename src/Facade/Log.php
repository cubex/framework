<?php
namespace Cubex\Facade;

use Cubex\Cubex;
use Illuminate\Support\Facades\Facade;
use Psr\Log\LogLevel;

/**
 * Class Log
 * @method static Cubex getFacadeApplication()
 */
class Log extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'log';
  }

  /**
   * All log levels in order of importance
   */
  public static $logLevels = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
    LogLevel::WARNING,
    LogLevel::NOTICE,
    LogLevel::INFO,
    LogLevel::DEBUG
  ];

  /**
   * System is unusable.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function emergency($message, array $context = array())
  {
    static::_log(LogLevel::EMERGENCY, $message, $context);
  }

  /**
   * Action must be taken immediately.
   *
   * Example: Entire website down, database unavailable, etc. This should
   * trigger the SMS alerts and wake you up.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function alert($message, array $context = array())
  {
    static::_log(LogLevel::ALERT, $message, $context);
  }

  /**
   * Critical conditions.
   *
   * Example: Application component unavailable, unexpected exception.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function critical($message, array $context = array())
  {
    static::_log(LogLevel::CRITICAL, $message, $context);
  }

  /**
   * Runtime errors that do not require immediate action but should typically
   * be logged and monitored.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function error($message, array $context = array())
  {
    static::_log(LogLevel::ERROR, $message, $context);
  }

  /**
   * Exceptional occurrences that are not errors.
   *
   * Example: Use of deprecated APIs, poor use of an API, undesirable things
   * that are not necessarily wrong.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function warning($message, array $context = array())
  {
    static::_log(LogLevel::WARNING, $message, $context);
  }

  /**
   * Normal but significant events.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function notice($message, array $context = array())
  {
    static::_log(LogLevel::NOTICE, $message, $context);
  }

  /**
   * Interesting events.
   *
   * Example: User logs in, SQL logs.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function info($message, array $context = array())
  {
    static::_log(LogLevel::INFO, $message, $context);
  }

  /**
   * Detailed debug information.
   *
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function debug($message, array $context = array())
  {
    static::_log(LogLevel::DEBUG, $message, $context);
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed  $level
   * @param string $message
   * @param array  $context
   *
   * @return null
   */
  public static function custom($level, $message, array $context = array())
  {
    static::_log($level, $message, $context);
  }

  /**
   * Is this log level allowed to log messages at the specified log level?
   *
   * @param string $messageLevel The level of the message to log
   * @param string $logLevel     The current maximum log level
   *
   * @return bool
   */
  public static function logLevelAllowed($messageLevel, $logLevel)
  {
    return array_search($messageLevel, self::$logLevels) <=
    array_search($logLevel, self::$logLevels);
  }

  protected static function _log($level, $message, array $context = array())
  {
    $instance   = new self;
    $logService = $instance->getFacadeRoot();
    $logService->writeLog($level, $message, $context);
  }
}
