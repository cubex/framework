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
 * A key is normally rotated because it is compromised, so anything that
 * decrypts with a fallback key may have been forged by whoever holds that
 * key. Keep the rotation window short, remove fallback keys once it ends,
 * and do not base security decisions on data decrypted during the window
 * (e.g. cookie contents). Consumers can re-issue such data so it is
 * encrypted under the current key.
 */
class Encrypter extends IlluminateEncrypter
{
  /**
   * @var IlluminateEncrypter[]
   */
  protected $_fallbacks = [];

  /**
   * @param string|string[] $key    a key, or a list of keys with the current
   *                                key first. Empty fallback keys are ignored.
   * @param string          $cipher
   *
   * @throws \RuntimeException when the current key is empty in a list, or any
   *                           key is not a valid length for the cipher
   */
  public function __construct($key, $cipher = 'AES-128-CBC')
  {
    if(!is_array($key))
    {
      parent::__construct($key, $cipher);
      return;
    }

    $keys = array_map('strval', array_values($key));
    $current = isset($keys[0]) ? $keys[0] : '';

    // An empty first entry is usually an unset environment variable. It is
    // rejected rather than replaced by the next entry, which would encrypt
    // new data with a key that is being retired.
    if($current === '')
    {
      throw new \RuntimeException(
        'The first encryption key is the current key and must not be empty.'
      );
    }

    parent::__construct($current, $cipher);

    foreach(array_slice($keys, 1) as $fallback)
    {
      if($fallback !== '')
      {
        $this->_fallbacks[] = new IlluminateEncrypter($fallback, $cipher);
      }
    }
  }

  public function decrypt($payload, $unserialize = true)
  {
    try
    {
      return parent::decrypt($payload, $unserialize);
    }
    catch(DecryptException $e)
    {
      foreach($this->_fallbacks as $fallback)
      {
        try
        {
          return $fallback->decrypt($payload, $unserialize);
        }
        catch(DecryptException $fallbackFailure)
        {
        }
      }
      throw $e;
    }
  }
}
