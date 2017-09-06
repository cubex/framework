<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\ServiceManager\Services\LogService;
use CubexTest\Cubex\Facade\TestLogger;
use Packaged\Config\Provider\ConfigSection;
use PHPUnit\Framework\TestCase;

class LogServiceTest extends TestCase
{
  public function testRegisterUsesSharedLogger()
  {
    $logger = new TestLogger();
    $cubex = new Cubex();
    $logService = new LogService();

    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      $logService
    );

    $logService->boot($cubex, new ConfigSection());

    $cubex->instance('log.logger', $logger);
    $logService->register();

    $this->assertSame($logger, $logService->getLogger());
  }
}
