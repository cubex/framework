<?php
namespace Cubex\ServiceManager;

use Cubex\Cubex;
use Packaged\Config\ConfigSectionInterface;

interface IServiceProvider
{
  /**
   * Boot the service
   *
   * @param Cubex                  $cubex
   * @param ConfigSectionInterface $config
   *
   * @return mixed
   */
  public function boot(Cubex $cubex, ConfigSectionInterface $config);

  /**
   * Register the service
   *
   * @param array $parameters
   *
   * @return mixed
   */
  public function register(array $parameters = null);

  /**
   * Shutdown the service
   * @return mixed
   */
  public function shutdown();
}
