<?php

namespace Cubex\Context\Flags;

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
    return $this->_request->query->has($flag);
  }
}
