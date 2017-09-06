<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\ServiceManager\Services\EncryptionService;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Config\Provider\Test\TestConfigProvider;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
  public function testRegisterCreatesEncrypter()
  {
    $cubex = new Cubex();
    $cubex->configure(new TestConfigProvider());
    $encryptionService = new EncryptionService();

    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      $encryptionService
    );

    $encryptionService->boot($cubex, new ConfigSection());

    $encryptionService->register();

    $encrypter = $cubex->make('encrypter');
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
  }
}
