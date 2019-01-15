<?php

namespace Cubex\Tests\Context;

use Cubex\Context\Context;
use Cubex\Cubex;
use Cubex\Tests\Supporting\TestContextAwareObject;
use Packaged\Config\Provider\ConfigProvider;
use Packaged\Helpers\Arrays;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;

class ContextTest extends TestCase
{
  public function testDefaults()
  {
    $ctx = new Context();
    $this->assertInstanceOf(ParameterBag::class, $ctx->meta());
    $this->assertInstanceOf(ConfigProvider::class, $ctx->config());
    $this->assertEquals(Context::ENV_LOCAL, $ctx->getEnvironment());
    $this->assertTrue($ctx->isCli());
    $this->assertNull($ctx->getCubex());
    $this->assertStringStartsWith('ctx-', $ctx->getId());
  }

  public function testEnvironment()
  {
    $pre = Arrays::value($_ENV, Context::_ENV_VAR);
    $_ENV[Context::_ENV_VAR] = Context::ENV_PHPUNIT;
    $ctx = new Context();
    $this->assertEquals(Context::ENV_PHPUNIT, $ctx->getEnvironment());
    $this->assertTrue($ctx->isEnv(Context::ENV_PHPUNIT));
    $this->assertFalse($ctx->isEnv(Context::ENV_LOCAL));
    if($pre == null)
    {
      unset($_ENV[Context::_ENV_VAR]);
    }
    else
    {
      $_ENV[Context::_ENV_VAR] = $pre;
    }
  }

  public function testCubexAware()
  {
    $ctx = new Context();
    $this->assertFalse($ctx->hasCubex());

    $cubex = new Cubex(__DIR__, null, false);
    $ctx->setCubex($cubex);
    $this->assertTrue($ctx->hasCubex());
    $this->assertSame($cubex, $ctx->getCubex());
  }

  public function testContextAware()
  {
    $obj = new TestContextAwareObject();
    $ctx = new Context();
    $this->assertFalse($obj->hasContext());
    $obj->setContext($ctx);
    $this->assertTrue($obj->hasContext());
    $this->assertSame($ctx, $obj->getContext());
    $obj->clearContext();
    $this->assertFalse($obj->hasContext());
  }
}
