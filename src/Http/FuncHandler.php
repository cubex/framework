<?php
namespace Cubex\Http;

use Cubex\Context\Context;
use Symfony\Component\HttpFoundation\Response;

class FuncHandler implements Handler
{
  /**
   * @var callable
   */
  protected $_func;

  public function __construct(callable $func)
  {
    $this->_func = $func;
  }

  public function handle(Context $c): Response
  {
    return ($this->_func)($c);
  }
}
