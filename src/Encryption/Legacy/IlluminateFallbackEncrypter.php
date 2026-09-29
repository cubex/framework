<?php
namespace Cubex\Encryption\Legacy;

use Cubex\Encryption\DecryptException;
use Cubex\Encryption\Encrypter;
use Cubex\Encryption\EncrypterInterface;

/**
 * Decrypts payloads written by illuminate/encryption 5.x (Cubex 2.6 and
 * earlier) as well as the current format. Everything is encrypted by the
 * wrapped encrypter; the legacy format is never written.
 *
 * Legacy payloads are base64(json({iv, value, mac})) using AES-128-CBC for a
 * 16 byte key or AES-256-CBC for a 32 byte key, authenticated with
 * HMAC-SHA256(key, iv . value). Each configured key of one of those lengths
 * is tried, raw, in order.
 *
 * Remove this class once payloads from before the upgrade have expired or
 * been re-issued.
 */
class IlluminateFallbackEncrypter implements EncrypterInterface
{
  /**
   * @var EncrypterInterface
   */
  protected $_encrypter;

  /**
   * [key, cipher] pairs
   *
   * @var array[]
   */
  protected $_legacyKeys = [];

  /**
   * @param EncrypterInterface $encrypter encrypts, and decrypts current payloads
   * @param string|string[]    $keys      raw configured keys, as for Encrypter
   */
  public function __construct(EncrypterInterface $encrypter, $keys)
  {
    $this->_encrypter = $encrypter;
    foreach(Encrypter::normaliseKeys($keys) as $key)
    {
      $cipher = static::cipherForKey($key);
      if($cipher !== null)
      {
        $this->_legacyKeys[] = [$key, $cipher];
      }
    }
  }

  /**
   * @param string $key
   *
   * @return string|null the cipher Illuminate used for a key of this length
   */
  public static function cipherForKey(string $key)
  {
    switch(strlen($key))
    {
      case 16:
        return 'AES-128-CBC';
      case 32:
        return 'AES-256-CBC';
    }
    return null;
  }

  public function encrypt(string $plaintext): string
  {
    return $this->_encrypter->encrypt($plaintext);
  }

  public function decrypt(string $payload): string
  {
    $legacy = static::_legacyPayload($payload);
    if($legacy === null)
    {
      return $this->_encrypter->decrypt($payload);
    }

    foreach($this->_legacyKeys as list($key, $cipher))
    {
      $plaintext = static::_legacyDecrypt($legacy, $key, $cipher);
      if($plaintext !== null)
      {
        return $plaintext;
      }
    }
    throw new DecryptException('The payload could not be decrypted with any configured key.');
  }

  /**
   * @param string $payload
   *
   * @return array|null the decoded payload, if it is in the legacy format
   */
  protected static function _legacyPayload(string $payload)
  {
    $decoded = json_decode(base64_decode($payload, true) ?: '', true);
    if(is_array($decoded)
      && isset($decoded['iv'], $decoded['value'], $decoded['mac'])
      && is_string($decoded['iv'])
      && is_string($decoded['value'])
      && is_string($decoded['mac']))
    {
      return $decoded;
    }
    return null;
  }

  /**
   * @return string|null the plaintext, or null when this key does not match
   */
  protected static function _legacyDecrypt(array $payload, string $key, string $cipher)
  {
    $iv = base64_decode($payload['iv'], true);
    if($iv === false || strlen($iv) !== openssl_cipher_iv_length($cipher))
    {
      return null;
    }

    $expected = hash_hmac('sha256', $payload['iv'] . $payload['value'], $key);
    if(!hash_equals($expected, $payload['mac']))
    {
      return null;
    }

    $plaintext = openssl_decrypt($payload['value'], $cipher, $key, 0, $iv);
    return $plaintext === false ? null : $plaintext;
  }
}
