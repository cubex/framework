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
   * rotation. encryption_cipher is optional (default AES-128-CBC), either one
   * cipher for every key or a list aligned with encryption_key. See Encrypter.
   *
   *   encryption_key[] = <current key>
   *   encryption_key[] = <previous key>
   *   encryption_cipher[] = AES-256-CBC
   *   encryption_cipher[] = AES-128-CBC
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
        $config = $cubex->getConfiguration();
        $key = $config->getItem('security', 'encryption_key', self::DEFAULT_KEY);
        $cipher = $config->getItem(
          'security',
          'encryption_cipher',
          Encrypter::DEFAULT_CIPHER
        );
        return new Encrypter(
          $key === [] ? self::DEFAULT_KEY : $key,
          $cipher === [] ? Encrypter::DEFAULT_CIPHER : $cipher
        );
      },
      true
    );
  }
}
