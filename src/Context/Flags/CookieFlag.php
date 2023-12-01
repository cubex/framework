<?php

namespace Cubex\Context\Flags;

use Packaged\Helpers\ValueAs;
use Packaged\Http\Request;

class CookieFlag implements FlagRule
{
  protected Request $_request;

  public function __construct(Request $request)
  {
    $this->_request = $request;
  }

  public function evaluate(string $flag): ?bool
  {
    if($this->_request->cookies->has($flag))
    {
      return ValueAs::bool($this->_request->cookies->get($flag) ?: 'true', true);
    }
    return null;
  }
}
