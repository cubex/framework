<?php

namespace Cubex\Attributes\Conditional;

use Attribute;

#[Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_ALL)]
class PreCondition extends AbstractConditionReturnsAttribute
{
}
