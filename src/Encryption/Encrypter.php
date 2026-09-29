<?php
namespace Cubex\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter as IlluminateEncrypter;

/**
 * Illuminate encrypter that accepts a single key, or a list of keys to allow
 * a key to be rotated without invalidating everything encrypted under the
 * old key at once.
 *
 * The first key in a list is the current key: it is used for all encryption
 * and tried first on decrypt. Later keys are decrypt-only fallbacks, tried in
 * order when the current key fails.
 *
 * The cipher is either one cipher for every key, or a list aligned by index
 * with the keys, so a rotation can also change cipher. An empty cipher is the
 * default AES-128-CBC.
 *
 * A key is normally rotated because it is compromised, so anything that
 * decrypts with a fallback key may have been forged by whoever holds that
 * key. Keep the rotation window short, remove fallback keys once it ends,
 * and do not base security decisions on data decrypted during the window
 * (e.g. cookie contents). Consumers can re-issue such data so it is
 * encrypted under the current key.
 */
class Encrypter extends IlluminateEncrypter
{
  const DEFAULT_CIPHER = 'AES-128-CBC';

  /**
   * @var IlluminateEncrypter[]
   */
  protected $_fallbacks = [];

  /**
   * @param string|string[] $key    a key, or a list of keys with the current
   *                                key first. Empty fallback keys are ignored.
   * @param string|string[] $cipher a cipher for every key, or a list with one
   *                                entry per key entry (including empty ones)
   *
   * @throws \RuntimeException when the current key is empty in a list, the
   *                           cipher list does not match the key list, or a
   *                           key is not valid for its cipher
   */
  public function __construct($key, $cipher = self::DEFAULT_CIPHER)
  {
    if(!is_array($key))
    {
      if(is_array($cipher))
      {
        throw new \RuntimeException(
          'A list of encryption ciphers requires a list of encryption keys.'
        );
      }
      parent::__construct($key, static::_cipher($cipher));
      return;
    }

    $keys = array_map('strval', array_values($key));
    if(is_array($cipher))
    {
      $ciphers = array_values($cipher);
      if(count($ciphers) !== count($keys))
      {
        throw new \RuntimeException(
          'The encryption cipher list must have one entry per encryption key.'
        );
      }
    }
    else
    {
      $ciphers = array_fill(0, count($keys), $cipher);
    }

    // An empty first entry is usually an unset environment variable. It is
    // rejected rather than replaced by the next entry, which would encrypt
    // new data with a key that is being retired.
    if(!isset($keys[0]) || $keys[0] === '')
    {
      throw new \RuntimeException(
        'The first encryption key is the current key and must not be empty.'
      );
    }

    parent::__construct($keys[0], static::_cipher($ciphers[0]));

    for($i = 1; $i < count($keys); $i++)
    {
      if($keys[$i] !== '')
      {
        $this->_fallbacks[] = new IlluminateEncrypter(
          $keys[$i],
          static::_cipher($ciphers[$i])
        );
      }
    }
  }

  protected static function _cipher($cipher)
  {
    $cipher = (string)$cipher;
    return $cipher === '' ? self::DEFAULT_CIPHER : $cipher;
  }

  public function decrypt($payload, $unserialize = true)
  {
    $failure = null;
    // null is the current key, handled by the parent
    foreach(array_merge([null], $this->_fallbacks) as $fallback)
    {
      try
      {
        return $fallback === null
          ? parent::decrypt($payload, $unserialize)
          : $fallback->decrypt($payload, $unserialize);
      }
      catch(DecryptException $e)
      {
        $failure = $failure ?: $e;
      }
    }
    throw $failure;
  }
}
