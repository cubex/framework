<?php
namespace CubexTest\Cubex\Encryption;

use Cubex\Encryption\DecryptException;
use Cubex\Encryption\Encrypter;
use Cubex\Encryption\EncrypterInterface;
use Illuminate\Encryption\Encrypter as IlluminateEncrypter;
use PHPUnit\Framework\TestCase;

class EncrypterTest extends TestCase
{
  const CURRENT = 'a-new-key-of-any-length-over-16';
  const OLDER = 'older-key-000002';

  protected static function _decode(string $payload): string
  {
    return base64_decode(strtr($payload, '-_', '+/'));
  }

  protected static function _encode(string $binary): string
  {
    return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
  }

  public function testRoundTrip()
  {
    $encrypter = new Encrypter(self::CURRENT);
    $this->assertInstanceOf(EncrypterInterface::class, $encrypter);
    foreach(['', 'value', random_bytes(1000)] as $plaintext)
    {
      $this->assertSame($plaintext, $encrypter->decrypt($encrypter->encrypt($plaintext)));
    }
  }

  public function testPayloadFormat()
  {
    $payload = (new Encrypter(self::CURRENT))->encrypt('value');
    $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $payload);
    $binary = self::_decode($payload);
    $this->assertSame(Encrypter::VERSION, $binary[0]);
    $this->assertSame(1 + 24 + strlen('value') + 16, strlen($binary));
  }

  public function testNonceIsRandom()
  {
    $encrypter = new Encrypter(self::CURRENT);
    $this->assertNotSame($encrypter->encrypt('value'), $encrypter->encrypt('value'));
  }

  public function testSmallerThanIlluminate()
  {
    $plaintext = serialize('a typical cookie value of some length');
    $ours = strlen((new Encrypter(self::OLDER))->encrypt($plaintext));
    $illuminate = strlen((new IlluminateEncrypter(self::OLDER))->encrypt($plaintext, false));
    // 57 plaintext bytes: 108 vs 272
    $this->assertLessThan($illuminate / 2, $ours);
  }

  public function testRotation()
  {
    $old = new Encrypter(self::OLDER);
    $rotating = new Encrypter([self::CURRENT, '', self::OLDER]);
    $this->assertSame('old', $rotating->decrypt($old->encrypt('old')));

    $payload = $rotating->encrypt('new');
    $this->assertSame('new', (new Encrypter(self::CURRENT))->decrypt($payload));
    $this->expectException(DecryptException::class);
    $old->decrypt($payload);
  }

  public function testWrongKeyThrows()
  {
    $payload = (new Encrypter(self::OLDER))->encrypt('value');
    $this->expectException(DecryptException::class);
    $this->expectExceptionMessage('any configured key');
    (new Encrypter(self::CURRENT))->decrypt($payload);
  }

  public function tamperProvider()
  {
    return [
      'version'    => [0],
      'nonce'      => [5],
      'ciphertext' => [26],
      'tag'        => [-1],
    ];
  }

  /**
   * @dataProvider tamperProvider
   */
  public function testTamperingThrows(int $offset)
  {
    $encrypter = new Encrypter(self::CURRENT);
    $binary = self::_decode($encrypter->encrypt('value'));
    $offset = $offset < 0 ? strlen($binary) + $offset : $offset;
    $binary[$offset] = chr(ord($binary[$offset]) ^ 1);
    $this->expectException(DecryptException::class);
    $encrypter->decrypt(self::_encode($binary));
  }

  public function testVersionIsAuthenticated()
  {
    // A payload claiming the current version must have been sealed with it
    $encrypter = new Encrypter(self::CURRENT);
    $binary = self::_decode($encrypter->encrypt('value'));
    $key = hash_hkdf('sha256', self::CURRENT, 32, Encrypter::KDF_INFO);
    $nonce = substr($binary, 1, 24);
    $forged = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt('value', "\x02", $nonce, $key);
    $this->expectException(DecryptException::class);
    $encrypter->decrypt(self::_encode(Encrypter::VERSION . $nonce . $forged));
  }

  public function malformedProvider()
  {
    return [
      'not base64url' => ['not a payload!', 'base64url'],
      'padded'        => ['AQ==', 'base64url'],
      'bad length'    => ['AAAAA', 'base64url'],
      'too short'     => [self::_encode("\x01short"), 'too short'],
      'bad version'   => [self::_encode("\x02" . str_repeat('a', 60)), 'version'],
    ];
  }

  /**
   * @dataProvider malformedProvider
   */
  public function testMalformedPayloadThrows(string $payload, string $message)
  {
    $this->expectException(DecryptException::class);
    $this->expectExceptionMessage($message);
    (new Encrypter(self::CURRENT))->decrypt($payload);
  }

  public function testIlluminatePayloadIsNotAccepted()
  {
    $payload = (new IlluminateEncrypter(self::OLDER))->encrypt('value');
    $this->expectException(DecryptException::class);
    (new Encrypter(self::OLDER))->decrypt($payload);
  }

  public function invalidKeyProvider()
  {
    return [
      'empty string'       => ['', 'must not be empty'],
      'empty list'         => [[], 'must not be empty'],
      'empty first entry'  => [['', self::OLDER], 'must not be empty'],
      'all empty'          => [['', ''], 'must not be empty'],
      'short current key'  => ['fifteen-bytes!!', 'at least 16 bytes'],
      'short previous key' => [[self::CURRENT, 'short'], 'at least 16 bytes'],
    ];
  }

  /**
   * @dataProvider invalidKeyProvider
   */
  public function testInvalidKeysThrow($keys, string $message)
  {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    new Encrypter($keys);
  }

  public function testNormaliseKeys()
  {
    $this->assertSame([self::CURRENT], Encrypter::normaliseKeys(self::CURRENT));
    $this->assertSame(
      [self::CURRENT, self::OLDER],
      Encrypter::normaliseKeys(['x' => self::CURRENT, '', null, self::OLDER])
    );
  }

  public function testMissingSodiumThrows()
  {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('ext-sodium or paragonie/sodium_compat');
    new NoSodiumEncrypter(self::CURRENT);
  }
}

class NoSodiumEncrypter extends Encrypter
{
  protected static function _sodiumAvailable(): bool
  {
    return false;
  }
}
