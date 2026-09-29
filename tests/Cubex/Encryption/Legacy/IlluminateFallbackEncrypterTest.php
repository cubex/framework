<?php
namespace CubexTest\Cubex\Encryption\Legacy;

use Cubex\Encryption\DecryptException;
use Cubex\Encryption\Encrypter;
use Cubex\Encryption\Legacy\IlluminateFallbackEncrypter;
use Illuminate\Encryption\Encrypter as IlluminateEncrypter;
use PHPUnit\Framework\TestCase;

class IlluminateFallbackEncrypterTest extends TestCase
{
  const NEW_KEY = 'a-new-key-of-any-length-over-16';
  const KEY_128 = 'older-key-000002';
  const KEY_256 = 'older-256-bit-key-00000000000003';

  protected function _encrypter($keys): IlluminateFallbackEncrypter
  {
    return new IlluminateFallbackEncrypter(new Encrypter($keys), $keys);
  }

  public function legacyKeyProvider()
  {
    return [
      'AES-128-CBC' => [self::KEY_128, 'AES-128-CBC'],
      'AES-256-CBC' => [self::KEY_256, 'AES-256-CBC'],
    ];
  }

  /**
   * @dataProvider legacyKeyProvider
   */
  public function testDecryptsIlluminatePayloads(string $key, string $cipher)
  {
    $illuminate = new IlluminateEncrypter($key, $cipher);
    $encrypter = $this->_encrypter([self::NEW_KEY, $key]);
    $this->assertSame('raw', $encrypter->decrypt($illuminate->encrypt('raw', false)));
    $this->assertSame(serialize(['a' => 1]), $encrypter->decrypt($illuminate->encrypt(['a' => 1])));

    // single key upgrade without rotation
    $this->assertSame('raw', $this->_encrypter($key)->decrypt($illuminate->encryptString('raw')));
  }

  /**
   * @dataProvider legacyKeyProvider
   */
  public function testNeverWritesLegacyFormat(string $key, string $cipher)
  {
    $encrypter = $this->_encrypter($key);
    $payload = $encrypter->encrypt('value');
    $this->assertSame('value', (new Encrypter($key))->decrypt($payload));
    $this->expectException(\Illuminate\Contracts\Encryption\DecryptException::class);
    (new IlluminateEncrypter($key, $cipher))->decrypt($payload, false);
  }

  public function testDecryptsCurrentFormat()
  {
    $encrypter = $this->_encrypter([self::NEW_KEY, self::KEY_128]);
    $this->assertSame('v', $encrypter->decrypt((new Encrypter(self::KEY_128))->encrypt('v')));
    $this->assertSame('v', $encrypter->decrypt($encrypter->encrypt('v')));
  }

  public function testUnknownLegacyKeyThrows()
  {
    $payload = (new IlluminateEncrypter('unknown-key-0004'))->encrypt('value');
    $this->expectException(DecryptException::class);
    $this->_encrypter([self::NEW_KEY, self::KEY_128])->decrypt($payload);
  }

  public function testKeysOfOtherLengthsAreNotLegacyKeys()
  {
    $this->assertSame('AES-128-CBC', IlluminateFallbackEncrypter::cipherForKey(self::KEY_128));
    $this->assertSame('AES-256-CBC', IlluminateFallbackEncrypter::cipherForKey(self::KEY_256));
    $this->assertNull(IlluminateFallbackEncrypter::cipherForKey(self::NEW_KEY));
  }

  public function tamperProvider()
  {
    return [
      'mac'   => ['mac', str_repeat('0', 64)],
      'value' => ['value', base64_encode('forged-ciphertext')],
      'iv'    => ['iv', base64_encode('short')],
    ];
  }

  /**
   * @dataProvider tamperProvider
   */
  public function testTamperedLegacyPayloadThrows(string $field, string $value)
  {
    $payload = json_decode(base64_decode((new IlluminateEncrypter(self::KEY_128))->encrypt('v')), true);
    $payload[$field] = $value;
    $this->expectException(DecryptException::class);
    $this->_encrypter(self::KEY_128)->decrypt(base64_encode(json_encode($payload)));
  }

  public function testUndecryptableLegacyCiphertextThrows()
  {
    // valid MAC over a value that is not valid ciphertext
    $iv = base64_encode(random_bytes(16));
    $value = base64_encode('not-a-block');
    $mac = hash_hmac('sha256', $iv . $value, self::KEY_128);
    $this->expectException(DecryptException::class);
    $this->_encrypter(self::KEY_128)->decrypt(base64_encode(json_encode(compact('iv', 'value', 'mac'))));
  }
}
