<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\Encryption\Encrypter;
use Cubex\Encryption\EncrypterInterface;
use Cubex\Encryption\IlluminateEncrypterAdapter;
use Cubex\Encryption\Legacy\IlluminateFallbackEncrypter;
use Cubex\ServiceManager\Services\EncryptionService;
use Illuminate\Encryption\Encrypter as IlluminateEncrypter;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Config\Provider\Test\TestConfigProvider;
use PHPUnit\Framework\TestCase;

class EncryptionServiceTest extends TestCase
{
  const NEW_KEY = 'a-new-key-of-any-length-over-16';
  const OLD_KEY = 'older-key-000002';

  protected function _cubex($keys = null): Cubex
  {
    $config = new TestConfigProvider();
    if($keys !== null)
    {
      $config->addItem('security', 'encryption_key', $keys);
    }
    $cubex = new Cubex();
    $cubex->configure($config);
    $encryptionService = new EncryptionService();
    $encryptionService->boot($cubex, new ConfigSection());
    $encryptionService->register();
    return $cubex;
  }

  public function testRegisterCreatesEncrypter()
  {
    $this->assertInstanceOf('\Cubex\ServiceManager\IServiceProvider', new EncryptionService());

    $cubex = $this->_cubex();
    $encrypter = $cubex->make('encrypter');
    $this->assertInstanceOf(IlluminateEncrypterAdapter::class, $encrypter);
    $this->assertInstanceOf(\Illuminate\Contracts\Encryption\Encrypter::class, $encrypter);
    $this->assertInstanceOf(IlluminateFallbackEncrypter::class, $cubex->make(EncrypterInterface::class));
    $this->assertSame($cubex->make(EncrypterInterface::class), $encrypter->getEncrypter());
  }

  public function defaultKeyProvider()
  {
    return ['missing' => [null], 'empty list' => [[]]];
  }

  /**
   * @dataProvider defaultKeyProvider
   */
  public function testDefaultKey($keys)
  {
    $core = $this->_cubex($keys)->make(EncrypterInterface::class);
    $this->assertSame('v', (new Encrypter(EncryptionService::DEFAULT_KEY))->decrypt($core->encrypt('v')));
    $legacy = (new IlluminateEncrypter(EncryptionService::DEFAULT_KEY))->encrypt('v', false);
    $this->assertSame('v', $core->decrypt($legacy));
  }

  public function testStringKey()
  {
    $core = $this->_cubex(self::NEW_KEY)->make(EncrypterInterface::class);
    $this->assertSame('v', (new Encrypter(self::NEW_KEY))->decrypt($core->encrypt('v')));
  }

  public function testKeyList()
  {
    $cubex = $this->_cubex([self::NEW_KEY, self::OLD_KEY]);
    $core = $cubex->make(EncrypterInterface::class);
    $this->assertSame('v', (new Encrypter(self::NEW_KEY))->decrypt($core->encrypt('v')));
    $this->assertSame('v', $core->decrypt((new Encrypter(self::OLD_KEY))->encrypt('v')));

    // an existing cookie written by Cubex 2.6 through EncryptCookies
    $cookie = (new IlluminateEncrypter(self::OLD_KEY))->encrypt(['id' => 5]);
    $this->assertSame(['id' => 5], $cubex->make('encrypter')->decrypt($cookie));
  }

  public function testEmptyFirstKeyThrows()
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->_cubex(['', self::OLD_KEY])->make('encrypter');
  }
}
