<?php
namespace Cubex\Encryption;

use Illuminate\Contracts\Encryption\DecryptException as IlluminateDecryptException;
use Illuminate\Contracts\Encryption\Encrypter as IlluminateEncrypter;

/**
 * Exposes an EncrypterInterface through Illuminate's encrypter contract, for
 * consumers such as Illuminate\Cookie\Middleware\EncryptCookies.
 *
 * Serialized values are unserialized with allowed_classes disabled, so a
 * decrypted payload can never instantiate objects; objects come back as
 * __PHP_Incomplete_Class.
 */
class IlluminateEncrypterAdapter implements IlluminateEncrypter
{
  /**
   * @var EncrypterInterface
   */
  protected $_encrypter;

  public function __construct(EncrypterInterface $encrypter)
  {
    $this->_encrypter = $encrypter;
  }

  public function getEncrypter(): EncrypterInterface
  {
    return $this->_encrypter;
  }

  public function encrypt($value, $serialize = true)
  {
    return $this->_encrypter->encrypt($serialize ? serialize($value) : (string)$value);
  }

  public function decrypt($payload, $unserialize = true)
  {
    try
    {
      $plaintext = $this->_encrypter->decrypt((string)$payload);
    }
    catch(DecryptException $e)
    {
      throw new IlluminateDecryptException($e->getMessage(), 0, $e);
    }

    if(!$unserialize)
    {
      return $plaintext;
    }

    $value = @unserialize($plaintext, ['allowed_classes' => false]);
    if($value === false && $plaintext !== serialize(false))
    {
      throw new IlluminateDecryptException('The payload could not be unserialized.');
    }
    return $value;
  }

  public function encryptString($value)
  {
    return $this->encrypt($value, false);
  }

  public function decryptString($payload)
  {
    return $this->decrypt($payload, false);
  }
}
