<?php
namespace Cubex\Encryption;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;

/**
 * Encrypts with the current key and decrypts with the current key or any
 * previous key, to allow an encryption key to be rotated without
 * invalidating everything encrypted under the old key at once.
 *
 * Previous keys are decrypt-only. A key is normally rotated because it is
 * compromised, so anything that decrypts with a previous key may have been
 * forged by whoever holds that key. Keep the rotation window short, remove
 * previous keys once it ends, and do not base security decisions on data
 * decrypted during the window (e.g. cookie contents). Consumers can re-issue
 * such data so it is encrypted under the current key.
 */
class RotatingEncrypter implements Encrypter
{
  /**
   * @var Encrypter
   */
  protected $_current;

  /**
   * @var Encrypter[]
   */
  protected $_previous;

  /**
   * @param Encrypter   $current  Used for all encryption, tried first on decrypt
   * @param Encrypter[] $previous Tried in order when the current key fails
   */
  public function __construct(Encrypter $current, array $previous = [])
  {
    $this->_current = $current;
    $this->_previous = [];
    foreach($previous as $encrypter)
    {
      $this->_addPrevious($encrypter);
    }
  }

  protected function _addPrevious(Encrypter $encrypter)
  {
    $this->_previous[] = $encrypter;
  }

  public function encrypt($value, $serialize = true)
  {
    return $this->_current->encrypt($value, $serialize);
  }

  public function decrypt($payload, $unserialize = true)
  {
    try
    {
      return $this->_current->decrypt($payload, $unserialize);
    }
    catch(DecryptException $e)
    {
      foreach($this->_previous as $encrypter)
      {
        try
        {
          return $encrypter->decrypt($payload, $unserialize);
        }
        catch(DecryptException $previousFailure)
        {
        }
      }
      throw $e;
    }
  }

  /**
   * The current encryption key
   *
   * @return string|null
   */
  public function getKey()
  {
    return method_exists($this->_current, 'getKey')
      ? $this->_current->getKey() : null;
  }
}
