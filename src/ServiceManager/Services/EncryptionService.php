<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\Encryption\Encrypter;

class EncryptionService extends AbstractServiceProvider
{
  const DEFAULT_KEY = 'mR?u7DP30sj5Djdf';

  /**
   * Register the service
   *
   * [security] encryption_key is either a single key, or a list of keys for
   * rotation, see Encrypter:
   *
   *   encryption_key[] = <current key>
   *   encryption_key[] = <previous key>
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
        $key = $cubex->getConfiguration()->getItem(
          'security',
          'encryption_key',
          self::DEFAULT_KEY
        );
        return new Encrypter($key === [] ? self::DEFAULT_KEY : $key);
      },
      true
    );
  }
}
