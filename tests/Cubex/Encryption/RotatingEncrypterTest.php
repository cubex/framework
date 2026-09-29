<?php
namespace CubexTest\Cubex\Encryption;

use Cubex\Encryption\RotatingEncrypter;
use Illuminate\Encryption\Encrypter;
use PHPUnit\Framework\TestCase;

class RotatingEncrypterTest extends TestCase
{
  const CURRENT = 'current-key-0001';
  const OLDER = 'older-key-000002';
  const OLDEST = 'oldest-key-00003';

  protected function _rotating()
  {
    return new RotatingEncrypter(
      new Encrypter(self::CURRENT),
      [new Encrypter(self::OLDER), new Encrypter(self::OLDEST)]
    );
  }

  public function testEncryptsWithCurrentKey()
  {
    $payload = $this->_rotating()->encrypt('value');
    $this->assertEquals('value', (new Encrypter(self::CURRENT))->decrypt($payload));
  }

  public function testDecryptsCurrentKey()
  {
    $rotating = $this->_rotating();
    $this->assertEquals('value', $rotating->decrypt($rotating->encrypt('value')));
    $this->assertEquals(
      'raw',
      $rotating->decrypt($rotating->encrypt('raw', false), false)
    );
  }

  public function testDecryptsPreviousKeys()
  {
    $rotating = $this->_rotating();
    $this->assertEquals(
      'older',
      $rotating->decrypt((new Encrypter(self::OLDER))->encrypt('older'))
    );
    $this->assertEquals(
      'oldest',
      $rotating->decrypt((new Encrypter(self::OLDEST))->encrypt('oldest', false), false)
    );
  }

  public function testThrowsWhenNoKeyMatches()
  {
    $payload = (new Encrypter('unknown-key-0004'))->encrypt('value');
    $this->expectException('\Illuminate\Contracts\Encryption\DecryptException');
    $this->_rotating()->decrypt($payload);
  }

  public function testGetKeyReturnsCurrentKey()
  {
    $this->assertEquals(self::CURRENT, $this->_rotating()->getKey());
  }
}
