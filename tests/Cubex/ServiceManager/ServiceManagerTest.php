<?php

class ServiceManagerTest extends PHPUnit_Framework_TestCase
{
  public function testCanAddService()
  {
    $cubex   = new \Cubex\Cubex();
    $manager = new \Cubex\ServiceManager\ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService("smtester", "TestService", []);

    $tester = $cubex->make('smtester');
    $this->assertInstanceOf('\Cubex\ServiceManager\IServiceProvider', $tester);
    $this->assertInstanceOf('TestService', $tester);

    $this->setExpectedException(
      '\RuntimeException',
      "The service 'smtester' has already been registered."
    );
    $manager->addService("smtester", "TestService", []);
  }

  public function testServicesShutDown()
  {
    $cubex   = new \Cubex\Cubex();
    $manager = new \Cubex\ServiceManager\ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService("smtester", "TestService", []);

    $tester = $cubex->make('smtester');
    $manager->shutdown();
    $this->assertTrue($tester->shutdown);
  }

  public function testServicesShutDownInCubex()
  {
    $cubex   = new \Cubex\Cubex();
    $manager = new \Cubex\ServiceManager\ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService("smtester", "TestService", []);
    $cubex->instance('service.manager', $manager);

    $tester = $cubex->make('smtester');
    $cubex->terminate(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      new \Symfony\Component\HttpFoundation\Response()
    );
    $this->assertTrue($tester->shutdown);
  }

  /**
   * @dataProvider exceptionProvider
   *
   * @param      $class
   * @param      $eType
   * @param      $eMsg
   * @param null $service
   * @param bool $destroyer
   */
  public function testRegisterExceptions(
    $class, $eType, $eMsg, $service = null, $destroyer = false
  )
  {
    if($service === null)
    {
      $service = 'service.test.' . md5($class);
    }

    $cubex   = new \Cubex\Cubex();
    $manager = new CorruptableServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService($service, $class, []);
    if($destroyer)
    {
      $manager->destroyService($service);
    }
    $this->setExpectedException($eType, $eMsg);
    $cubex->make($service);
  }

  public function exceptionProvider()
  {
    return [
      ['stdClass', '\Exception', "'stdClass' is not a valid IServiceProvider"],
      [
        'thisDoesNotExist',
        '\RuntimeException',
        "'thisDoesNotExist' could not be loaded for the 'tester' service'",
        'tester'
      ],
      [
        'stdClass',
        '\RuntimeException',
        "You are attempting to load 'testerx', " .
        "however no configuration exists",
        'testerx',
        true
      ],
    ];
  }
}

class CorruptableServiceManager extends \Cubex\ServiceManager\ServiceManager
{
  public function destroyService($service)
  {
    unset($this->_services[$service]);
  }
}

class TestService implements \Cubex\ServiceManager\IServiceProvider
{
  public $shutdown = false;

  public function boot(
    \Cubex\Cubex $cubex, \Packaged\Config\ConfigSectionInterface $config
  )
  {
  }

  public function register(array $parameters = null)
  {
  }

  public function shutdown()
  {
    $this->shutdown = true;
  }
}
