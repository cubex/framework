<?php
namespace CubexTest\Cubex\Encryption;

use Cubex\Encryption\Encrypter;
use Illuminate\Encryption\Encrypter as IlluminateEncrypter;
use PHPUnit\Framework\TestCase;

class EncrypterTest extends TestCase
{
  const CURRENT = 'current-key-0001';
  const OLDER = 'older-key-000002';
  const OLDEST = 'oldest-key-00003';

  protected function _rotating()
  {
    return new Encrypter([self::CURRENT, self::OLDER, self::OLDEST]);
  }

  public function testIsIlluminateEncrypter()
  {
    $this->assertInstanceOf(IlluminateEncrypter::class, new Encrypter(self::CURRENT));
    $this->assertInstanceOf(IlluminateEncrypter::class, $this->_rotating());
  }

  public function testStringKeyUnchanged()
  {
    $encrypter = new Encrypter(self::CURRENT);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $payload = $encrypter->encrypt('value');
    $this->assertEquals('value', (new IlluminateEncrypter(self::CURRENT))->decrypt($payload));
    $this->assertEquals(
      'value',
      $encrypter->decrypt((new IlluminateEncrypter(self::CURRENT))->encrypt('value'))
    );
  }

  public function testStringKeyNoMatchThrows()
  {
    $payload = (new IlluminateEncrypter(self::OLDER))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    (new Encrypter(self::CURRENT))->decrypt($payload);
  }

  public function testFirstEntryEncrypts()
  {
    $encrypter = $this->_rotating();
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $payload = $encrypter->encrypt('value');
    $this->assertEquals('value', (new IlluminateEncrypter(self::CURRENT))->decrypt($payload));
    $this->assertEquals('value', $encrypter->decrypt($payload));
    $this->assertEquals('raw', $encrypter->decryptString($encrypter->encryptString('raw')));
  }

  public function testFallbackKeysDecrypt()
  {
    $encrypter = $this->_rotating();
    $this->assertEquals(
      'older',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('older'))
    );
    $this->assertEquals(
      'oldest',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDEST))->encrypt('oldest', false), false)
    );
  }

  public function testNoKeyMatchesThrows()
  {
    $payload = (new IlluminateEncrypter('unknown-key-0004'))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $this->_rotating()->decrypt($payload);
  }

  public function testEmptyFallbacksIgnored()
  {
    $encrypter = new Encrypter([self::CURRENT, '', null, self::OLDER]);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $this->assertEquals(
      'older',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('older'))
    );
  }

  public function testSingleEntryArrayMatchesString()
  {
    $encrypter = new Encrypter([self::CURRENT]);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $payload = (new IlluminateEncrypter(self::OLDER))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $encrypter->decrypt($payload);
  }

  public function testEmptyFirstEntryThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('current key');
    new Encrypter(['', self::OLDER]);
  }

  public function testAllEmptyThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('current key');
    new Encrypter(['', '']);
  }

  public function testInvalidFallbackKeyLengthThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('correct key lengths');
    new Encrypter([self::CURRENT, 'too-short']);
  }

  public function testInvalidCurrentKeyLengthThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('correct key lengths');
    new Encrypter(['too-short', self::OLDER]);
  }

  public function testCipherAppliesToAllKeys()
  {
    $current = str_repeat('a', 32);
    $older = str_repeat('b', 32);
    $encrypter = new Encrypter([$current, $older], 'AES-256-CBC');
    $this->assertEquals(
      'older',
      $encrypter->decrypt((new IlluminateEncrypter($older, 'AES-256-CBC'))->encrypt('older'))
    );
    $this->expectException('\RuntimeException');
    new Encrypter([$current, self::OLDER], 'AES-256-CBC');
  }

  public function testDefaultCipherUnchanged()
  {
    $encrypter = new Encrypter([self::CURRENT, self::OLDER]);
    $payload = $encrypter->encrypt('value');
    $this->assertEquals(
      'value',
      (new IlluminateEncrypter(self::CURRENT, 'AES-128-CBC'))->decrypt($payload)
    );
  }

  public function testCipherPerKey()
  {
    $current = str_repeat('a', 32);
    $encrypter = new Encrypter(
      [$current, self::OLDER],
      ['AES-256-CBC', 'AES-128-CBC']
    );
    $this->assertEquals(
      'new',
      (new IlluminateEncrypter($current, 'AES-256-CBC'))->decrypt($encrypter->encrypt('new'))
    );
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('old'))
    );
  }

  public function testCipherListStaysAlignedWithEmptyKeys()
  {
    $current = str_repeat('a', 32);
    $encrypter = new Encrypter(
      [$current, '', self::OLDER],
      ['AES-256-CBC', 'AES-256-CBC', 'AES-128-CBC']
    );
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('old'))
    );
  }

  public function testEmptyCipherIsDefault()
  {
    $encrypter = new Encrypter([self::CURRENT, self::OLDER], ['', null]);
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER))->encrypt('old'))
    );
    $this->assertEquals(self::CURRENT, (new Encrypter(self::CURRENT, ''))->getKey());
  }

  public function testMismatchedCipherListThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('one entry per encryption key');
    new Encrypter([self::CURRENT, self::OLDER], ['AES-128-CBC']);
  }

  public function testCipherListWithStringKeyThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('requires a list of encryption keys');
    new Encrypter(self::CURRENT, ['AES-128-CBC']);
  }

  public function testUnsupportedCipherThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('supported ciphers');
    new Encrypter([self::CURRENT, self::OLDER], ['AES-128-CBC', 'DES']);
  }

  public function testWrongKeyLengthForCipherThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('correct key lengths');
    new Encrypter([self::CURRENT, self::OLDER], ['AES-256-CBC', 'AES-128-CBC']);
  }
}
