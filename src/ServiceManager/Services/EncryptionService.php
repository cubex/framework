<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\Encryption\RotatingEncrypter;
use Illuminate\Encryption\Encrypter;

class EncryptionService extends AbstractServiceProvider
{
  const DEFAULT_KEY = 'mR?u7DP30sj5Djdf';

  /**
   * Register the service
   *
   * [security] encryption_key is either a single key, or a list of keys for
   * rotation:
   *
   *   encryption_key[] = <current key>
   *   encryption_key[] = <previous key>
   *
   * The first entry encrypts; later entries are decrypt-only previous keys,
   * see RotatingEncrypter for the security implications.
   *
   * @param array $parameters
   *
   * @return mixed
   */
  public function register(array $parameters = null)
  {
    $this->getCubex()->bind(
      'encrypter',
      function (Cubex $cubex)
      {
        return static::createEncrypter(
          $cubex->getConfiguration()->getItem(
            'security',
            'encryption_key',
            self::DEFAULT_KEY
          )
        );
      },
      true
    );
  }

  /**
   * @param string|string[] $keys a key, or a list of keys with the current
   *                              key first. Empty previous keys are ignored.
   *
   * @return \Illuminate\Contracts\Encryption\Encrypter
   *
   * @throws \RuntimeException when the current key is empty or any key is not
   *                           a valid length for the cipher
   */
  public static function createEncrypter($keys)
  {
    if(!is_array($keys))
    {
      return new Encrypter($keys);
    }

    $keys = array_values($keys);
    if(empty($keys))
    {
      return new Encrypter(self::DEFAULT_KEY);
    }

    $current = (string)$keys[0];
    $previous = array_values(
      array_filter(array_map('strval', array_slice($keys, 1)), 'strlen')
    );

    // An empty first entry is usually an unset environment variable. It is
    // rejected rather than replaced by the next entry, which would encrypt
    // new data with a key that is being retired.
    if($current === '')
    {
      throw new \RuntimeException(
        'The first encryption_key entry is the current key and must not be empty.'
      );
    }

    $currentEncrypter = new Encrypter($current);
    if(empty($previous))
    {
      return $currentEncrypter;
    }

    $previousEncrypters = [];
    foreach($previous as $key)
    {
      $previousEncrypters[] = new Encrypter($key);
    }
    return new RotatingEncrypter($currentEncrypter, $previousEncrypters);
  }
}
