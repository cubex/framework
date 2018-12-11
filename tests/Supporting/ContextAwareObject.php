<?php
namespace Cubex\Tests\Supporting;

use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;

class ContextAwareObject implements ContextAware
{
  use ContextAwareTrait;
}
