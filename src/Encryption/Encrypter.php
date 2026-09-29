<?php
namespace Cubex\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\EncryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

/**
 * AES-CBC encrypter producing and reading the same payloads as
 * illuminate/encryption 5.x: base64(json({iv, value, mac})), where value is
 * the base64 openssl ciphertext and mac is an HMAC-SHA256 of iv.value keyed
 * with the encryption key.
 *
 * Accepts a single key, or a list of keys to allow a key to be rotated
 * without invalidating everything encrypted under the old key at once.
 *
 * The first key in a list is the current key: it is used for all encryption
 * and tried first on decrypt. Later keys are decrypt-only fallbacks, tried in
 * order when the current key fails.
 *
 * Each key's cipher follows its length: a 16 byte key uses AES-128-CBC and
 * a 32 byte key AES-256-CBC, so a rotation can also move to AES-256-CBC.
 *
 * A key is normally rotated because it is compromised, so anything that
 * decrypts with a fallback key may have been forged by whoever holds that
 * key. Keep the rotation window short, remove fallback keys once it ends,
 * and do not base security decisions on data decrypted during the window
 * (e.g. cookie contents). Consumers can re-issue such data so it is
 * encrypted under the current key.
 */
class Encrypter implements EncrypterContract
{
  /**
   * [key, cipher] pairs, current key first
   *
   * @var array[]
   */
  protected $_keys = [];

  /**
   * @param string|string[] $key a key, or a list of keys with the current key
   *                             first. Empty fallback keys are ignored.
   *
   * @throws \RuntimeException when the current key is empty in a list, or a
   *                           key is not 16 or 32 bytes
   */
  public function __construct($key)
  {
    if(!is_array($key))
    {
      $this->_addKey((string)$key);
      return;
    }

    $keys = array_map('strval', array_values($key));

    // An empty first entry is usually an unset environment variable. It is
    // rejected rather than replaced by the next entry, which would encrypt
    // new data with a key that is being retired.
    if(!isset($keys[0]) || $keys[0] === '')
    {
      throw new \RuntimeException(
        'The first encryption key is the current key and must not be empty.'
      );
    }

    foreach($keys as $i => $k)
    {
      if($i === 0 || $k !== '')
      {
        $this->_addKey($k);
      }
    }
  }

  protected function _addKey($key)
  {
    $cipher = static::cipherForKey($key);
    if($cipher === null)
    {
      throw new \RuntimeException(
        'Encryption keys must be 16 bytes (AES-128-CBC) or 32 bytes (AES-256-CBC).'
      );
    }
    $this->_keys[] = [$key, $cipher];
  }

  /**
   * @param string $key
   *
   * @return string|null the cipher for a key of this length, null if none
   */
  public static function cipherForKey($key)
  {
    switch(mb_strlen($key, '8bit'))
    {
      case 16:
        return 'AES-128-CBC';
      case 32:
        return 'AES-256-CBC';
    }
    return null;
  }

  /**
   * The current encryption key
   *
   * @return string
   */
  public function getKey()
  {
    return $this->_keys[0][0];
  }

  /**
   * @param mixed $value
   * @param bool  $serialize
   *
   * @return string
   *
   * @throws EncryptException
   */
  public function encrypt($value, $serialize = true)
  {
    list($key, $cipher) = $this->_keys[0];
    $iv = random_bytes(openssl_cipher_iv_length($cipher));

    $value = openssl_encrypt(
      $serialize ? serialize($value) : $value,
      $cipher,
      $key,
      0,
      $iv
    );
    if($value === false)
    {
      throw new EncryptException('Could not encrypt the data.');
    }

    $iv = base64_encode($iv);
    $mac = static::_hash($iv, $value, $key);
    $json = json_encode(compact('iv', 'value', 'mac'));
    if(json_last_error() !== JSON_ERROR_NONE)
    {
      throw new EncryptException('Could not encrypt the data.');
    }
    return base64_encode($json);
  }

  /**
   * @param string $value
   *
   * @return string
   */
  public function encryptString($value)
  {
    return $this->encrypt($value, false);
  }

  /**
   * With $unserialize, the decrypted value is passed to unserialize()
   * unrestricted, as illuminate/encryption does. When the payload may come
   * from an untrusted source (e.g. a cookie), pass $unserialize = false and
   * unserialize with ['allowed_classes' => false].
   *
   * @param string $payload
   * @param bool   $unserialize
   *
   * @return mixed
   *
   * @throws DecryptException when no key can decrypt the payload
   */
  public function decrypt($payload, $unserialize = true)
  {
    $failure = null;
    foreach($this->_keys as $keyCipher)
    {
      try
      {
        $decrypted = static::_decrypt($payload, $keyCipher[0], $keyCipher[1]);
        return $unserialize ? unserialize($decrypted) : $decrypted;
      }
      catch(DecryptException $e)
      {
        $failure = $failure ?: $e;
      }
    }
    throw $failure;
  }

  /**
   * @param string $payload
   *
   * @return string
   */
  public function decryptString($payload)
  {
    return $this->decrypt($payload, false);
  }

  protected static function _decrypt($payload, $key, $cipher)
  {
    $payload = json_decode(base64_decode($payload), true);
    if(!is_array($payload)
      || !isset($payload['iv'], $payload['value'], $payload['mac'])
      || !is_string($payload['iv'])
      || !is_string($payload['value'])
      || !is_string($payload['mac'])
      || strlen(base64_decode($payload['iv'], true))
      !== openssl_cipher_iv_length($cipher))
    {
      throw new DecryptException('The payload is invalid.');
    }

    // Compare HMACs of both MACs under a random key, as illuminate/encryption
    // does, so the comparison leaks nothing about the expected MAC.
    $bytes = random_bytes(16);
    $calculated = hash_hmac(
      'sha256',
      static::_hash($payload['iv'], $payload['value'], $key),
      $bytes,
      true
    );
    if(!hash_equals(hash_hmac('sha256', $payload['mac'], $bytes, true), $calculated))
    {
      throw new DecryptException('The MAC is invalid.');
    }

    $decrypted = openssl_decrypt(
      $payload['value'],
      $cipher,
      $key,
      0,
      base64_decode($payload['iv'])
    );
    if($decrypted === false)
    {
      throw new DecryptException('Could not decrypt the data.');
    }
    return $decrypted;
  }

  protected static function _hash($iv, $value, $key)
  {
    return hash_hmac('sha256', $iv . $value, $key);
  }
}
