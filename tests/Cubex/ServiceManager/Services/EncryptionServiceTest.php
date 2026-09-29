<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\Encryption\RotatingEncrypter;
use Cubex\ServiceManager\Services\EncryptionService;
use Illuminate\Encryption\Encrypter;
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
    $encryptionService = new EncryptionService();
    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      $encryptionService
    );

    $encrypter = $this->_encrypter();
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
    $this->assertEquals(EncryptionService::DEFAULT_KEY, $encrypter->getKey());
  }

  public function testStringKey()
  {
    $encrypter = $this->_encrypter(self::CURRENT);
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
  }

  public function testSingleEntryArrayMatchesString()
  {
    $encrypter = $this->_encrypter([self::CURRENT]);
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());

    $encrypter = $this->_encrypter([self::CURRENT, '', null]);
    $this->assertInstanceOf('\Illuminate\Encryption\Encrypter', $encrypter);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
  }

  public function testEmptyArrayUsesDefaultKey()
  {
    $this->assertEquals(
      EncryptionService::DEFAULT_KEY,
      $this->_encrypter([])->getKey()
    );
  }

  public function testArrayRotatesKeys()
  {
    $encrypter = $this->_encrypter([self::CURRENT, '', self::OLDER]);
    $this->assertInstanceOf(RotatingEncrypter::class, $encrypter);

    // first entry encrypts
    $payload = $encrypter->encrypt('new');
    $this->assertEquals('new', (new Encrypter(self::CURRENT))->decrypt($payload));

    // later entries decrypt
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new Encrypter(self::OLDER))->encrypt('old'))
    );
  }

  public function testArrayThrowsWhenNoKeyMatches()
  {
    $encrypter = $this->_encrypter([self::CURRENT, self::OLDER]);
    $payload = (new Encrypter('unknown-key-0004'))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $encrypter->decrypt($payload);
  }

  public function testEmptyFirstEntryWithPreviousKeysThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('current key');
    $this->_encrypter(['', self::OLDER]);
  }

  public function testEmptyOnlyEntryThrows()
  {
    $this->expectException('\RuntimeException');
    $this->_encrypter(['']);
  }

  public function testInvalidPreviousKeyLengthThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('correct key lengths');
    $this->_encrypter([self::CURRENT, 'too-short']);
  }

  public function testInvalidCurrentKeyLengthThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('correct key lengths');
    $this->_encrypter(['too-short', self::OLDER]);
  }
}
