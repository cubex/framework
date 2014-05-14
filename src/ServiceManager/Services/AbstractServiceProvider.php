<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\ICubexAware;
use Cubex\ServiceManager\IServiceProvider;
use Packaged\Config\ConfigSectionInterface;

abstract class AbstractServiceProvider implements IServiceProvider, ICubexAware
{
  /**
   * @var ConfigSectionInterface
   */
  protected $_config;

  use CubexAwareTrait;

  /**
   * @inheritdoc
   */
  public function boot(Cubex $cubex, ConfigSectionInterface $config)
  {
    $this->setCubex($cubex);
    $this->_config = $config;
  }

  /**
   * Retrieve the service configuration
   *
   * @return ConfigSectionInterface
   */
  public function getConfig()
  {
    return $this->_config;
  }

  /**
   * Get a specific item from the config
   *
   * @param $key
   * @param $default
   *
   * @return mixed
   */
  public function getConfigItem($key, $default = null)
  {
    return $this->getConfig()->getItem($key, $default);
  }

  /**
   * Register the service
   *
   * @param array $parameters
   *
   * @return mixed
   */
  public function register(array $parameters = null)
  {
  }

  /**
   * @inheritdoc
   */
  public function shutdown()
  {
  }
}
