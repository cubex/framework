<?php
namespace CubexTest\Cubex\ServiceManager;

use Cubex\Cubex;
use Cubex\ServiceManager\IServiceProvider;
use Cubex\ServiceManager\ServiceManager;
use Packaged\Config\ConfigSectionInterface;
use Packaged\Config\Provider\Test\TestConfigProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServiceManagerTest extends \PHPUnit_Framework_TestCase
{
  public function testCanAddService()
  {
    $cubex = new Cubex();
    $manager = new ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService(
      "smtester",
      'CubexTest\Cubex\ServiceManager\TestService',
      []
    );

    $tester = $cubex->make('smtester');
    $this->assertInstanceOf('\Cubex\ServiceManager\IServiceProvider', $tester);
    $this->assertInstanceOf(
      'CubexTest\Cubex\ServiceManager\TestService',
      $tester
    );

    $this->setExpectedException(
      '\RuntimeException',
      "The service 'smtester' has already been registered."
    );
    $manager->addService(
      "smtester",
      'CubexTest\Cubex\ServiceManager\TestService',
      []
    );
  }

  public function testServicesShutDown()
  {
    $cubex = new Cubex();
    $manager = new ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService(
      "smtester",
      'CubexTest\Cubex\ServiceManager\TestService',
      []
    );

    $tester = $cubex->make('smtester');
    $manager->shutdown();
    $this->assertTrue($tester->shutdown);
  }

  public function testServicesShutDownInCubex()
  {
    $cubex = new Cubex();
    $manager = new ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $manager->addService(
      "smtester",
      'CubexTest\Cubex\ServiceManager\TestService',
      []
    );
    $cubex->instance('service.manager', $manager);

    $tester = $cubex->make('smtester');
    $cubex->terminate(Request::createFromGlobals(), new Response());
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

    $cubex = new Cubex();
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

  public function testServiceAliases()
  {
    $cubex = new Cubex();
    $cubex->configure(new TestConfigProvider());
    $manager = new ServiceManager();
    $manager->setCubex($cubex);
    $manager->boot();
    $encrypter = $cubex->make('encrypter');
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
  }
}

class CorruptableServiceManager extends ServiceManager
{
  public function destroyService($service)
  {
    unset($this->_services[$service]);
  }
}

class TestService implements IServiceProvider
{
  public $shutdown = false;

  public function boot(
    Cubex $cubex, ConfigSectionInterface $config
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
