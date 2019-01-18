<?php

namespace Cubex\Tests;

use Cubex\Console\Console;
use Cubex\Context\Context;
use Cubex\Cubex;
use Cubex\Http\FuncHandler;
use Cubex\Routing\Router;
use Cubex\Tests\Supporting\Console\TestExceptionCommand;
use Cubex\Tests\Supporting\Http\TestResponse;
use Exception;
use Packaged\Config\Provider\ConfigProvider;
use Packaged\Http\Request;
use Packaged\Http\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class CubexTest extends TestCase
{
  protected function _cubex()
  {
    return new Cubex(__DIR__, null, false);
  }

  public function testProjectRoot()
  {
    $cubex = $this->_cubex();
    $this->assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
  }

  public function testLogger()
  {
    $cubex = $this->_cubex();
    $this->assertInstanceOf(NullLogger::class, $cubex->getLogger());
    $logger = new NullLogger();
    $cubex->setLogger($logger);
    $this->assertEquals($logger, $cubex->getLogger());
  }

  public function testInstance()
  {
    $this->_cubex();
    $this->assertNull(Cubex::instance());
    $cubex = new Cubex(__DIR__, null, true);
    $this->assertEquals($cubex, Cubex::instance());
    Cubex::destroyGlobalInstance();
    $this->assertNull(Cubex::instance());
  }

  /**
   * @throws Throwable
   */
  public function testContextConfig()
  {
    $cubex = new Cubex(__DIR__, null, false);
    $this->assertTrue($cubex->getContext()->config()->has('testing'));
    $this->assertEquals('value1', $cubex->getContext()->config()->getItem('testing', 'key_one'));

    $cubex = new Cubex('missing_directory 1', null, false);
    $this->assertInstanceOf(ConfigProvider::class, $cubex->getContext()->config());
  }

  /**
   * @throws Throwable
   */
  public function testHandle()
  {
    $cubex = $this->_cubex();
    $router = new Router();
    $router->handle('/', new FuncHandler(function () { return new TestResponse('All OK'); }));
    /** @var TestResponse $response */
    $response = $cubex->handle($router);
    $this->assertInstanceOf(TestResponse::class, $response);
    $this->assertEquals('All OK', $response->getSendResult());
  }

  /**
   * @throws Throwable
   */
  public function testHandleCompleteException()
  {
    $cubex = $this->_cubex();
    $logger = new TestLogger();
    $cubex->setLogger($logger);
    $router = new Router();
    $router->handle('/', new FuncHandler(function () { return new TestResponse('All OK'); }));
    $cubex->listen(
      Cubex::EVENT_HANDLE_COMPLETE,
      function () { throw new Exception("Complete Exception", 500); }
    );
    $cubex->handle($router);
    $this->assertTrue($logger->hasError("Complete Exception"));

    $this->expectExceptionMessage("Complete Exception");
    $cubex->handle($router, true, false);
  }

  public function testHandleEvents()
  {
    $cubex = $this->_cubex();
    $context = $cubex->getContext();
    $cubex->listen(
      Cubex::EVENT_HANDLE_START,
      function (Context $c) { $c->meta()->set(Cubex::EVENT_HANDLE_START, true); }
    );
    $cubex->listen(
      Cubex::EVENT_HANDLE_PRE_EXECUTE,
      function (Context $c) { $c->meta()->set(Cubex::EVENT_HANDLE_PRE_EXECUTE, true); }
    );
    $cubex->listen(
      Cubex::EVENT_HANDLE_RESPONSE_PREPARE,
      function (Context $c) { $c->meta()->set(Cubex::EVENT_HANDLE_RESPONSE_PREPARE, true); }
    );
    $cubex->listen(
      Cubex::EVENT_HANDLE_COMPLETE,
      function (Context $c) { $c->meta()->set(Cubex::EVENT_HANDLE_COMPLETE, true); }
    );

    $router = new Router();
    $router->handle('/', new FuncHandler(function () { return new Response('All OK'); }));
    $cubex->handle($router, false, true);

    $this->assertTrue($context->meta()->has(Cubex::EVENT_HANDLE_START));
    $this->assertTrue($context->meta()->has(Cubex::EVENT_HANDLE_PRE_EXECUTE));
    $this->assertTrue($context->meta()->has(Cubex::EVENT_HANDLE_RESPONSE_PREPARE));
    $this->assertTrue($context->meta()->has(Cubex::EVENT_HANDLE_COMPLETE));
  }

  /**
   * @throws Throwable
   */
  public function testNoHandler()
  {
    $cubex = $this->_cubex();
    $this->expectExceptionMessage(Cubex::ERROR_NO_HANDLER);
    $cubex->handle(new Router(), false, false);
  }

  /**
   * @throws Throwable
   */
  public function testExceptionHandler()
  {
    $cubex = $this->_cubex();
    $response = $cubex->handle(new Router(), false);
    $this->assertEquals(500, $response->getStatusCode());
    $this->assertContains(Cubex::ERROR_NO_HANDLER, $response->getContent());
  }

  public function testLog()
  {
    $cubex = new Cubex(__DIR__, null, true);
    $logger = new TestLogger();
    $cubex->setLogger($logger);
    Cubex::log()->error("TEST");
    $this->assertTrue($logger->hasError("TEST"));
    $this->assertFalse($logger->hasError("NOTHING"));
    Cubex::destroyGlobalInstance();
  }

  public function testGetContext()
  {
    $cubex = $this->_cubex();

    $this->assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
    $cubex->removeShared(Context::class);

    $cubex->factory(Context::class, function () { return new Context(); });
    $this->assertNull($cubex->getContext()->getProjectRoot());

    $cubex->removeShared(Context::class);
    $cubex->removeFactory(Context::class);
    $this->assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
  }

  /**
   * @throws \Exception
   */
  public function testCli()
  {
    $cubex = $this->_cubex();
    $input = new ArgvInput([]);
    $output = new BufferedOutput();
    $result = $cubex->cli($input, $output);
    $this->assertIsInt($result);
    $this->assertEquals(0, $result);
    $this->assertStringStartsWith('Cubex Console', $output->fetch());
  }

  /**
   * @throws \Exception
   */
  public function testCliException()
  {
    $cubex = $this->_cubex();
    $cubex->listen(
      Cubex::EVENT_CONSOLE_PREPARE,
      function (Context $context, Console $console) {
        $console->setCatchExceptions(false);
        $console->add(new TestExceptionCommand());
        $context->meta()->set('TestExceptionCommand', true);
      }
    );
    $input = new ArgvInput(['', 'TestExceptionCommand']);
    $output = new BufferedOutput();
    $result = $cubex->cli($input, $output);
    $this->assertIsInt($result);
    $this->assertEquals(1, $result);
    $this->assertContains('GENERIC EXCEPTION', $output->fetch());
  }

  public function testContextFactory()
  {
    $factory = function () {
      $ctx = new Context(Request::createFromGlobals());
      $ctx->meta()->set("abc", 'xyz');
      return $ctx;
    };
    $cubex = $this->_cubex();
    $cubex->factory(Context::class, $factory);
    $ctx = $cubex->getContext();
    $this->assertEquals("xyz", $ctx->meta()->get('abc'));
  }
}
