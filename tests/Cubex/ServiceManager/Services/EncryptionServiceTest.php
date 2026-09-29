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

  protected function _encrypter($key = null, $cipher = null)
  {
    $config = new TestConfigProvider();
    if($key !== null)
    {
      $config->addItem('security', 'encryption_key', $key);
    }
    if($cipher !== null)
    {
      $config->addItem('security', 'encryption_cipher', $cipher);
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

  public function testStringCipher()
  {
    $key = str_repeat('a', 32);
    $encrypter = $this->_encrypter($key, 'AES-256-CBC');
    $this->assertEquals(
      'value',
      (new IlluminateEncrypter($key, 'AES-256-CBC'))->decrypt($encrypter->encrypt('value'))
    );
  }

  public function testArrayCipher()
  {
    $key = str_repeat('a', 32);
    $encrypter = $this->_encrypter(
      [$key, self::OLDER],
      ['AES-256-CBC', 'AES-128-CBC']
    );
    $this->assertEquals(
      'new',
      (new IlluminateEncrypter($key, 'AES-256-CBC'))->decrypt($encrypter->encrypt('new'))
    );
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('old'))
    );
  }

  public function testEmptyCipherListUsesDefault()
  {
    $encrypter = $this->_encrypter(self::CURRENT, []);
    $this->assertEquals(
      'value',
      (new IlluminateEncrypter(self::CURRENT))->decrypt($encrypter->encrypt('value'))
    );
  }
}
