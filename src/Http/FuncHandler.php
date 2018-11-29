<?php
namespace Cubex\Http;

use Cubex\Context\Context;

class FuncHandler implements Handler
{
  protected $_func;

  public function __construct(callable $func)
  {
    $this->_func = $func;
  }

  public function handle(Context $c, Response $w, Request $r)
  {
    call_user_func($this->_func, $c, $w, $r);
  }
}
