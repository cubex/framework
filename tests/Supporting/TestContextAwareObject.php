<?php
namespace Cubex\Tests\Supporting;

use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;

class TestContextAwareObject implements ContextAware
{
  use ContextAwareTrait;
}
