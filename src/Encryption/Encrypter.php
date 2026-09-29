<?php
namespace Cubex\Encryption;

/**
 * Authenticated encryption with XChaCha20-Poly1305 (IETF). Requires ext-sodium
 * or paragonie/sodium_compat.
 *
 * Payload: base64url, unpadded, of
 *   version (1 byte) || nonce (24 bytes) || ciphertext || tag (16 bytes)
 * The version byte is bound as additional data, so it cannot be altered.
 *
 * Keys are a single key or a list of keys. The first key is the current key:
 * it encrypts and is tried first on decrypt. Later keys are decrypt-only, to
 * allow a key to be rotated without invalidating existing payloads at once.
 * Each configured key must be at least 16 bytes; the 32 byte cipher key is
 * derived from it with HKDF-SHA256 using the info string KDF_INFO.
 *
 * A key is normally rotated because it is compromised, so anything that
 * decrypts with a previous key may have been forged by whoever holds that
 * key. Keep the rotation window short, remove previous keys once it ends,
 * and do not base security decisions on data decrypted during the window.
 */
class Encrypter implements EncrypterInterface
{
  const VERSION = "\x01";
  const KDF_INFO = 'cubex-encryption-v1-xchacha20poly1305';
  const MIN_KEY_BYTES = 16;
  const KEY_BYTES = 32;
  const NONCE_BYTES = 24;
  const TAG_BYTES = 16;

  /**
   * Derived cipher keys, current key first
   *
   * @var string[]
   */
  protected $_keys = [];

  /**
   * @param string|string[] $keys a key, or a list of keys with the current key
   *                              first. Empty previous keys are ignored.
   *
   * @throws \InvalidArgumentException when the current key is empty or any
   *                                   key is shorter than MIN_KEY_BYTES
   * @throws \RuntimeException         when sodium is unavailable
   */
  public function __construct($keys)
  {
    if(!static::_sodiumAvailable())
    {
      throw new \RuntimeException(
        'Cubex\\Encryption requires ext-sodium or paragonie/sodium_compat.'
      );
    }

    foreach(static::normaliseKeys($keys) as $key)
    {
      if(strlen($key) < static::MIN_KEY_BYTES)
      {
        throw new \InvalidArgumentException(
          'Encryption keys must be at least ' . static::MIN_KEY_BYTES . ' bytes.'
        );
      }
      $this->_keys[] = hash_hkdf('sha256', $key, static::KEY_BYTES, static::KDF_INFO);
    }
  }

  protected static function _sodiumAvailable(): bool
  {
    // paragonie/sodium_compat defines the same functions
    return function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt');
  }

  /**
   * @param string|string[] $keys
   *
   * @return string[] the non-empty keys, current key first
   *
   * @throws \InvalidArgumentException when the current key is empty
   */
  public static function normaliseKeys($keys): array
  {
    $keys = array_map('strval', is_array($keys) ? array_values($keys) : [$keys]);

    // An empty first entry is usually an unset environment variable. It is
    // rejected rather than replaced by the next entry, which would encrypt
    // new data with a key that is being retired.
    if(!isset($keys[0]) || $keys[0] === '')
    {
      throw new \InvalidArgumentException(
        'The first encryption key is the current key and must not be empty.'
      );
    }
    return array_values(array_filter($keys, 'strlen'));
  }

  public function encrypt(string $plaintext): string
  {
    $nonce = random_bytes(static::NONCE_BYTES);
    $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
      $plaintext,
      static::VERSION,
      $nonce,
      $this->_keys[0]
    );
    return rtrim(strtr(base64_encode(static::VERSION . $nonce . $ciphertext), '+/', '-_'), '=');
  }

  public function decrypt(string $payload): string
  {
    $binary = false;
    if(strlen($payload) % 4 !== 1 && preg_match('/^[A-Za-z0-9_-]*$/', $payload))
    {
      $binary = base64_decode(strtr($payload, '-_', '+/'), true);
    }
    if($binary === false)
    {
      throw new DecryptException('The payload is not valid base64url.');
    }

    $headerBytes = 1 + static::NONCE_BYTES;
    if(strlen($binary) < $headerBytes + static::TAG_BYTES)
    {
      throw new DecryptException('The payload is too short.');
    }
    $version = $binary[0];
    if($version !== static::VERSION)
    {
      throw new DecryptException('The payload version is not supported.');
    }

    $nonce = substr($binary, 1, static::NONCE_BYTES);
    $ciphertext = substr($binary, $headerBytes);
    foreach($this->_keys as $key)
    {
      $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, $version, $nonce, $key);
      if($plaintext !== false)
      {
        return $plaintext;
      }
    }
    throw new DecryptException('The payload could not be decrypted with any configured key.');
  }
}
