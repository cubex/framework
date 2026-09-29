<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\Encryption\Encrypter;
use Cubex\Encryption\EncrypterInterface;
use Cubex\Encryption\IlluminateEncrypterAdapter;
use Cubex\Encryption\Legacy\IlluminateFallbackEncrypter;

class EncryptionService extends AbstractServiceProvider
{
  const DEFAULT_KEY = 'mR?u7DP30sj5Djdf';

  /**
   * Register the service
   *
   * Binds EncrypterInterface, and 'encrypter' as Illuminate's encrypter
   * contract. [security] encryption_key is either a single key, or a list of
   * keys for rotation with the current key first, see Encrypter:
   *
   *   encryption_key[] = <current key>
   *   encryption_key[] = <previous key>
   *
   * Payloads written by Cubex 2.6 (illuminate/encryption) still decrypt, see
   * IlluminateFallbackEncrypter.
   *
   * @param array $parameters
   *
   * @return mixed
   */
  public function register(array $parameters = null)
  {
    $this->getCubex()->bind(
      EncrypterInterface::class,
      function (Cubex $cubex)
      {
        $keys = $cubex->getConfiguration()->getItem('security', 'encryption_key', self::DEFAULT_KEY);
        if($keys === [])
        {
          $keys = self::DEFAULT_KEY;
        }
        return new IlluminateFallbackEncrypter(new Encrypter($keys), $keys);
      },
      true
    );

    $this->getCubex()->bind(
      'encrypter',
      function (Cubex $cubex)
      {
        return new IlluminateEncrypterAdapter($cubex->make(EncrypterInterface::class));
      },
      true
    );
  }
}
