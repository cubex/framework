<?php

class LogServiceTest extends PHPUnit_Framework_TestCase
{
  public function testRegisterUsesSharedLogger()
  {
    $logger     = new TestLogger();
    $cubex      = new \Cubex\Cubex();
    $logService = new \Cubex\ServiceManager\Services\LogService();

    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      $logService
    );

    $logService->boot(
      $cubex,
      new \Packaged\Config\Provider\ConfigSection()
    );

    $cubex->instance('log.logger', $logger);
    $logService->register();

    $this->assertSame($logger, $logService->getLogger());
  }
}
