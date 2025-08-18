<?php

namespace Cubex\Attributes;

use Attribute;

#[Attribute(\Attribute::IS_REPEATABLE)]
class SkipCondition extends AbstractConditionAttribute
{
}
