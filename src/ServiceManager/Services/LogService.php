<?php
namespace Cubex\ServiceManager\Services;

use Cubex\ServiceManager\IServiceProvider;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class LogService extends AbstractServiceProvider implements IServiceProvider
{
  /**
   * @var LoggerInterface
   */
  protected $_logger;

  /**
   * Register the service
   *
   * @param array $parameters
   *
   * @return mixed
   */
  public function register(array $parameters = null)
  {
    $cubex = $this->getCubex();
    if($cubex->isShared('log.logger'))
    {
      $this->setLogger($cubex->make('log.logger'));
    }
    else
    {
      $logger = new Logger(
        $this->_config->getItem("log_name", $this->getCubex()->env())
      );
      $cubex->instance('log.logger', $logger);
      $this->setLogger($logger);
    }
  }

  /**
   * Replace the logger on the service
   *
   * @param LoggerInterface $logger
   *
   * @return $this
   */
  public function setLogger(LoggerInterface $logger)
  {
    $this->_logger = $logger;
    return $this;
  }

  /**
   * Retrieve the connected logger
   *
   * @return LoggerInterface|null
   */
  public function getLogger()
  {
    return $this->_logger;
  }

  /**
   * Send a log event to the logger
   *
   * @param      $level
   * @param      $message
   * @param null $context
   *
   * @return mixed
   */
  public function writeLog($level, $message, $context = null)
  {
    return $this->_logger->$level($message, $context);
  }
}
