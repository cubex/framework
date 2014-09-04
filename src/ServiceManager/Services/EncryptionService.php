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
    $this->getCubex()->bindShared(
      'encrypter',
      function (Cubex $cubex)
      {
        return new Encrypter(
          $cubex->getConfiguration()->getItem(
            'security',
            'encryption_key',
            'BµlÇe.8)æ<¤§¬FòÊ¼&ø\'ð'
          )
        );
      }
    );
  }
}
