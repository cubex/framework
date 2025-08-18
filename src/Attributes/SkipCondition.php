<?php

namespace Cubex\Attributes;

use Attribute;

#[Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_ALL)]
class SkipCondition extends AbstractConditionAttribute
{
}
