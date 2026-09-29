<?php
namespace Cubex\Encryption;

interface EncrypterInterface
{
  /**
   * @return string an authenticated, URL-safe payload
   */
  public function encrypt(string $plaintext): string;

  /**
   * @throws DecryptException when the payload is malformed, tampered with, or
   *                          was not encrypted with a configured key
   */
  public function decrypt(string $payload): string;
}
