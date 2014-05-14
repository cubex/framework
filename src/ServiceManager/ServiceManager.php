<?php
namespace Cubex\ServiceManager;

use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\ICubexAware;
use Packaged\Config\Provider\ConfigSection;

class ServiceManager implements ICubexAware
{
  use CubexAwareTrait;

  /**
   * Services to regiser
   * @var array
   */
  protected $_services = [];

  /**
   * Aliases for service register, used when a service will register instances
   *
   * @var array
   */
  protected $_aliases = [];

  /**
   * Maintain the registered services so they can be correctly shutdown
   * @var array
   */
  protected $_registeredServices = [];

  /**
   * Registered Service Names
   * @var array
   */
  protected $_serviceRegister = [];

  /**
   * Registered the services to cubex, without creating each service
   */
  public function boot()
  {
    $this->_processConfiguration();
    foreach(array_keys($this->_services) as $service)
    {
      $this->_bindService($service);
    }

    //Loop over all aliases to make sure the service is registered
    foreach($this->_aliases as $alias => $service)
    {
      $this->getCubex()->bind(
        $alias,
        function (Cubex $cubex) use ($alias, $service)
        {
          //Destroy the alias binding
          $cubex->forgetInstance($alias);

          //Force the service to register
          $cubex->make($service);

          //Make the service defined binding
          return $cubex->make($alias);
        }
      );
    }
  }

  /**
   * Bind the service loader to cubex
   *
   * @param $service
   */
  protected function _bindService($service)
  {
    //Bind the service manager register
    $this->_cubex->bind(
      $service,
      function ($cubex, $parameters) use ($service)
      {
        return $this->_register($cubex, $service, $parameters);
      },
      true
    );
  }

  /**
   * Process the cubex configuration to read out the relevant service configs
   */
  protected function _processConfiguration()
  {
    $this->_services['log'] = [
      '\Cubex\ServiceManager\Services\LogService',
      ['log_name' => 'misc']
    ];

    //Register the encryption service
    $this->_services['encryption'] = [
      '\Cubex\ServiceManager\Services\EncryptionService',
      [],
    ];

    //Setup Aliases
    $this->_aliases['encrypter'] = 'encryption';

    //Register the auth service
    $this->_services['auth'] = [
      '\Cubex\ServiceManager\Services\AuthService',
      [],
    ];
  }

  /**
   * Create the service and return the instance
   *
   * @param Cubex $cubex
   * @param       $service
   * @param array $params
   *
   * @return mixed
   * @throws \RuntimeException
   * @throws \Exception
   */
  protected function _register($cubex, $service, $params = array())
  {
    if(!isset($this->_services[$service]))
    {
      throw new \RuntimeException(
        "You are attempting to load '$service'," .
        " however no configuration exists",
        500
      );
    }

    list($class, $config) = $this->_services[$service];

    if(!class_exists($class))
    {
      throw new \RuntimeException(
        "'$class' could not be loaded for the '$service' service'"
      );
    }

    $instance = new $class;

    if(!($instance instanceof IServiceProvider))
    {
      throw new \Exception("'$class' is not a valid IServiceProvider");
    }

    $instance->boot($cubex, new ConfigSection($service, $config));
    $instance->register($params);

    //Add the service to the register, to support service shutdown
    $this->_registeredServices[] = $instance;

    //Add the service to the register to stop addition of the same service
    $this->_serviceRegister[$service] = true;

    //Stop the service manager from being called after service creation
    $cubex->instance($service, $instance);

    return $instance;
  }

  /**
   * Add a new service to the manager
   *
   * @param       $service
   * @param       $class
   * @param array $config
   *
   * @return $this
   * @throws \RuntimeException
   */
  public function addService($service, $class, array $config)
  {
    if(isset($this->_serviceRegister[$service]))
    {
      throw new \RuntimeException(
        "The service '$service' has already been registered."
      );
    }

    $this->_services[$service] = [$class, $config];
    $this->_bindService($service);
    return $this;
  }

  /**
   * Shutdown all registered services
   */
  public function shutdown()
  {
    //Shutdown all services
    foreach($this->_registeredServices as $name => $service)
    {
      if($service instanceof IServiceProvider)
      {
        $service->shutdown();
      }
      unset($this->_registeredServices[$name]);
    }
  }
}
