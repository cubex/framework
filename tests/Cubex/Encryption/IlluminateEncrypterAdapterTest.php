<?php
namespace CubexTest\Cubex\Encryption;

use Cubex\Encryption\Encrypter;
use Cubex\Encryption\IlluminateEncrypterAdapter;
use Illuminate\Contracts\Encryption\DecryptException;
use PHPUnit\Framework\TestCase;

class IlluminateEncrypterAdapterTest extends TestCase
{
  const KEY = 'adapter-test-key';

  protected function _adapter(): IlluminateEncrypterAdapter
  {
    return new IlluminateEncrypterAdapter(new Encrypter(self::KEY));
  }

  public function testImplementsContract()
  {
    $adapter = $this->_adapter();
    $this->assertInstanceOf(\Illuminate\Contracts\Encryption\Encrypter::class, $adapter);
    $this->assertInstanceOf(Encrypter::class, $adapter->getEncrypter());
  }

  public function testSerializedRoundTrip()
  {
    $adapter = $this->_adapter();
    foreach([['a' => 1], 'string', 42, false, null] as $value)
    {
      $this->assertSame($value, $adapter->decrypt($adapter->encrypt($value)));
    }
  }

  public function testRawRoundTrip()
  {
    $adapter = $this->_adapter();
    $this->assertSame('raw', $adapter->decrypt($adapter->encrypt('raw', false), false));
    $this->assertSame('raw', $adapter->decryptString($adapter->encryptString('raw')));
    $this->assertSame('raw', (new Encrypter(self::KEY))->decrypt($adapter->encryptString('raw')));
  }

  public function testObjectsAreNotInstantiated()
  {
    $adapter = $this->_adapter();
    $payload = $adapter->encrypt(new AdapterTestObject());
    $value = $adapter->decrypt($payload);
    $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $value);
    $this->assertSame(0, AdapterTestObject::$woken);
  }

  public function testDecryptFailureThrowsContractException()
  {
    $this->expectException(DecryptException::class);
    $this->_adapter()->decrypt('not a payload!');
  }

  public function testUnserializeFailureThrows()
  {
    $adapter = $this->_adapter();
    $this->expectException(DecryptException::class);
    $this->expectExceptionMessage('unserialized');
    $adapter->decrypt($adapter->encrypt('not serialized', false));
  }
}

class AdapterTestObject
{
  public static $woken = 0;

  public function __wakeup()
  {
    static::$woken++;
  }
}
