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
  const KEY_256 = 'current-256-bit-key-000000000001';

  protected function _rotating()
  {
    return new Encrypter([self::CURRENT, self::OLDER, self::OLDEST]);
  }

  public function keyProvider()
  {
    return [
      'AES-128-CBC' => [self::CURRENT, 'AES-128-CBC'],
      'AES-256-CBC' => [self::KEY_256, 'AES-256-CBC'],
    ];
  }

  public function testImplementsContract()
  {
    $this->assertInstanceOf(
      '\Illuminate\Contracts\Encryption\Encrypter',
      new Encrypter(self::CURRENT)
    );
  }

  public function testCipherFollowsKeyLength()
  {
    $this->assertEquals('AES-128-CBC', Encrypter::cipherForKey(self::CURRENT));
    $this->assertEquals('AES-256-CBC', Encrypter::cipherForKey(self::KEY_256));
    $this->assertNull(Encrypter::cipherForKey('too-short'));
  }

  /**
   * @dataProvider keyProvider
   */
  public function testIlluminatePayloadDecrypts($key, $cipher)
  {
    $illuminate = new IlluminateEncrypter($key, $cipher);
    $ours = new Encrypter($key);
    $value = ['a' => 1, 'b' => 'two'];
    $this->assertEquals($value, $ours->decrypt($illuminate->encrypt($value)));
    $this->assertEquals('raw', $ours->decrypt($illuminate->encrypt('raw', false), false));
    $this->assertEquals('raw', $ours->decryptString($illuminate->encryptString('raw')));
  }

  /**
   * @dataProvider keyProvider
   */
  public function testIlluminateDecryptsOurPayload($key, $cipher)
  {
    $illuminate = new IlluminateEncrypter($key, $cipher);
    $ours = new Encrypter($key);
    $value = ['a' => 1, 'b' => 'two'];
    $this->assertEquals($value, $illuminate->decrypt($ours->encrypt($value)));
    $this->assertEquals('raw', $illuminate->decrypt($ours->encrypt('raw', false), false));
    $this->assertEquals('raw', $illuminate->decryptString($ours->encryptString('raw')));
  }

  public function testPayloadFormat()
  {
    $payload = json_decode(base64_decode((new Encrypter(self::CURRENT))->encrypt('v')), true);
    $this->assertEquals(['iv', 'value', 'mac'], array_keys($payload));
    $this->assertEquals(16, strlen(base64_decode($payload['iv'], true)));
    $this->assertEquals(
      hash_hmac('sha256', $payload['iv'] . $payload['value'], self::CURRENT),
      $payload['mac']
    );
  }

  public function testTamperedMacThrows()
  {
    $payload = json_decode(base64_decode((new Encrypter(self::CURRENT))->encrypt('v')), true);
    $payload['mac'] = hash_hmac('sha256', 'forged', self::CURRENT);
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $this->expectExceptionMessage('MAC is invalid');
    (new Encrypter(self::CURRENT))->decrypt(base64_encode(json_encode($payload)));
  }

  public function testTamperedValueThrows()
  {
    $payload = json_decode(base64_decode((new Encrypter(self::CURRENT))->encrypt('v')), true);
    $payload['value'] = base64_encode('forged');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    (new Encrypter(self::CURRENT))->decrypt(base64_encode(json_encode($payload)));
  }

  public function invalidPayloadProvider()
  {
    return [
      'not base64 json' => ['not a payload'],
      'missing mac'     => [base64_encode(json_encode(['iv' => base64_encode(str_repeat('a', 16)), 'value' => 'x']))],
      'short iv'        => [base64_encode(json_encode(['iv' => base64_encode('a'), 'value' => 'x', 'mac' => 'y']))],
      'array value'     => [base64_encode(json_encode(['iv' => base64_encode(str_repeat('a', 16)), 'value' => [], 'mac' => 'y']))],
    ];
  }

  /**
   * @dataProvider invalidPayloadProvider
   */
  public function testInvalidPayloadThrows($payload)
  {
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $this->expectExceptionMessage('payload is invalid');
    $this->_rotating()->decrypt($payload);
  }

  public function testStringKeyUnchanged()
  {
    $encrypter = new Encrypter(self::CURRENT);
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $this->assertEquals('value', $encrypter->decrypt($encrypter->encrypt('value')));
  }

  public function testStringKeyNoMatchThrows()
  {
    $payload = (new Encrypter(self::OLDER))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    (new Encrypter(self::CURRENT))->decrypt($payload);
  }

  public function testFirstEntryEncrypts()
  {
    $encrypter = $this->_rotating();
    $this->assertEquals(self::CURRENT, $encrypter->getKey());
    $payload = $encrypter->encrypt('value');
    $this->assertEquals('value', (new IlluminateEncrypter(self::CURRENT))->decrypt($payload));
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    (new IlluminateEncrypter(self::OLDER))->decrypt($payload);
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

  public function testMixedCipherRotation()
  {
    $encrypter = new Encrypter([self::KEY_256, self::OLDER]);
    $this->assertEquals(
      'new',
      (new IlluminateEncrypter(self::KEY_256, 'AES-256-CBC'))->decrypt($encrypter->encrypt('new'))
    );
    $this->assertEquals(
      'old',
      $encrypter->decrypt((new IlluminateEncrypter(self::OLDER, 'AES-128-CBC'))->encrypt('old'))
    );
  }

  public function testNoKeyMatchesThrowsFirstFailure()
  {
    $payload = (new Encrypter('unknown-key-0004'))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $this->expectExceptionMessage('MAC is invalid');
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

  public function testEmptyStringKeyThrows()
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('16 bytes');
    new Encrypter('');
  }

  public function badKeyProvider()
  {
    return [
      'short current'  => [['too-short', self::OLDER]],
      'short fallback' => [[self::CURRENT, 'too-short']],
      '24 byte key'    => [str_repeat('a', 24)],
    ];
  }

  /**
   * @dataProvider badKeyProvider
   */
  public function testBadKeyLengthThrows($key)
  {
    $this->expectException('\RuntimeException');
    $this->expectExceptionMessage('16 bytes');
    new Encrypter($key);
  }
}
