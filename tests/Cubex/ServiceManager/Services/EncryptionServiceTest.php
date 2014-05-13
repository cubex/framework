<?php

class EncryptionServiceTest extends PHPUnit_Framework_TestCase
{
  public function testRegisterCreatesEncrypter()
  {
    $cubex = new \Cubex\Cubex();
    $cubex->configure(new \Packaged\Config\Provider\Test\TestConfigProvider());
    $encryptionService = new \Cubex\ServiceManager\Services\EncryptionService();

    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      $encryptionService
    );

    $encryptionService->boot(
      $cubex,
      new \Packaged\Config\Provider\ConfigSection()
    );

    $encryptionService->register();

    $encrypter = $cubex->make('encrypter');
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
  }
}
