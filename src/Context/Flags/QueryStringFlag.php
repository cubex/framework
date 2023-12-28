<?php

namespace Cubex\Context\Flags;

use Packaged\Helpers\ValueAs;
use Packaged\Http\Request;

class QueryStringFlag implements FlagRule
{
  protected Request $_request;

  public function __construct(Request $request)
  {
    $this->_request = $request;
  }

  public function evaluate(string $flag): ?bool
  {
    if($this->_request->query->has($flag))
    {
      return ValueAs::bool($this->_request->query->get($flag) ?: 'true', true);
    }
    return null;
  }
}
