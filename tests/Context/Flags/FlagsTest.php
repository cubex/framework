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
    $ruleSet = new RuleSet();
    $ruleSet->set('test', true);
    $ruleSet->set('not', false);

    $flags = new Flags();
    $ruleSet->apply($flags);

    $this->assertTrue($flags->has('test', false));
    $this->assertFalse($flags->has('not', true));
  }
}
