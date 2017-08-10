<?php
namespace Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Illuminate\Encryption\Encrypter;

class EncryptionService extends AbstractServiceProvider
{
  /**
   * Register the service
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
        return new Encrypter(
          $cubex->getConfiguration()->getItem(
            'security',
            'encryption_key',
            'mR?u7DP30sj5Djdf'
          )
        );
      },
      true
    );
  }
}
