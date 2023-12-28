<?php

namespace Cubex\Tests\Context\Flags;

use Cubex\Context\Flags\Flags;
use Cubex\Context\Flags\RuleSet;
use PHPUnit\Framework\TestCase;

class FlagsTest extends TestCase
{
  public function testSetters()
  {
    $flags = new Flags();
    // Test Defaults
    $this->assertFalse($flags->has('test', false));
    $this->assertTrue($flags->has('test', true));

    // Test Setter
    $flags->set('setter', true);
    $this->assertTrue($flags->has('setter', false));
    $flags->set('test', false);
    $this->assertFalse($flags->has('test', true));

    // Test Enable
    $flags->enable('test');
    $this->assertTrue($flags->has('test', false));

    // Test Disable
    $flags->disable('test');
    $this->assertFalse($flags->has('test', true));

    // Test Remove
    $flags->remove('test');
    $this->assertFalse($flags->has('test', false));
    $this->assertTrue($flags->has('test', true));
  }

  public function testRules()
  {
    $flags = new Flags();

    $ruleSet = new RuleSet();
    $ruleSet->set('test', true);
    $ruleSet->set('test2', false);
    $ruleSet->set('not', false);
    $ruleSet->apply($flags);

    $globalRuleSet = new RuleSet();
    $globalRuleSet->set('test', true);
    $globalRuleSet->set('test2', true);// Expect first rule set to deny
    $globalRuleSet->set('test3', true);
    $flags->addRule($globalRuleSet);

    $this->assertTrue($flags->has('test', false));
    $this->assertFalse($flags->has('test2', false));
    $this->assertTrue($flags->has('test3', true));
    $this->assertFalse($flags->has('not', true));
  }
}
