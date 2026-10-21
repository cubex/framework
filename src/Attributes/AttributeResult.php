<?php

namespace Cubex\Attributes;

use Packaged\Context\Context;

interface AttributeResult
{
  /**
   * Returns null or a value to be returned from the method
   *
   * @param Context $ctx
   *
   * @return mixed
   */
  public function process(Context $ctx): mixed;
}
