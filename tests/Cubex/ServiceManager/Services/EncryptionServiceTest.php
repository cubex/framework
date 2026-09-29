<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\Encryption\Encrypter;
use Cubex\ServiceManager\Services\EncryptionService;
use Illuminate\Encryption\Encrypter as IlluminateEncrypter;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Config\Provider\Test\TestConfigProvider;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
  const CURRENT = 'current-key-0001';
  const OLDER = 'older-key-000002';

  protected function _encrypter($key = null)
  {
    $config = new TestConfigProvider();
    if($key !== null)
    {
      $config->addItem('security', 'encryption_key', $key);
    }
    $cubex = new Cubex();
    $cubex->configure($config);
    $encryptionService = new EncryptionService();
    $encryptionService->boot($cubex, new ConfigSection());
    $encryptionService->register();
    return $cubex->make('encrypter');
  }

  public function testRegisterCreatesEncrypter()
  {
    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      new EncryptionService()
    );

    $encrypter = $this->_encrypter();
    $this->assertInstanceOf(Encrypter::class, $encrypter);
    $this->assertInstanceOf(IlluminateEncrypter::class, $encrypter);
    $this->assertEquals(EncryptionService::DEFAULT_KEY, $encrypter->getKey());
  }

  public function testEmptyArrayUsesDefaultKey()
  {
    $this->assertEquals(
      EncryptionService::DEFAULT_KEY,
      $this->_encrypter([])->getKey()
    );
  }

  public function testStringKey()
  {
    $encrypter = $this->_encrypter(self::CURRENT);
    $this->assertInstanceOf(Encrypter::class, $encrypter);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
  }

  public function testArrayKeys()
  {
    $encrypter = $this->_encrypter([self::CURRENT, self::OLDER]);
    $this->assertInstanceOf(Encrypter::class, $encrypter);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('old'))
    );
  }

  public function testEmptyFirstEntryThrows()
  {
    $this->expectException('\RuntimeException');
    $this->_encrypter(['', self::OLDER]);
  }
}
