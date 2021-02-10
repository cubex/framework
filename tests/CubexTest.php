<?php

namespace Cubex\Tests;

use Cubex\Console\Console;
use Cubex\Console\Events\ConsoleCreateEvent;
use Cubex\Console\Events\ConsolePrepareEvent;
use Cubex\Cubex;
use Cubex\Events\Handle\HandleCompleteEvent;
use Cubex\Events\Handle\ResponsePrepareEvent;
use Cubex\Events\PreExecuteEvent;
use Cubex\Events\ShutdownEvent;
use Cubex\Logger\ErrorLogLogger;
use Cubex\Routing\Router;
use Cubex\Tests\Supporting\Console\TestExceptionCommand;
use Cubex\Tests\Supporting\Http\TestResponse;
use Exception;
use Packaged\Config\ConfigProviderInterface;
use Packaged\Context\Context;
use Packaged\Http\Request;
use Packaged\Http\Response;
use Packaged\Routing\ConditionHandler;
use Packaged\Routing\Handler\FuncHandler;
use Packaged\Routing\Handler\Handler;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
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
    self::assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
  }

  public function testLogger()
  {
    $cubex = $this->_cubex();
    $ctx = $cubex->getContext();
    $cXLogger = $ctx instanceof \Cubex\Context\Context ? $ctx->log() : $cubex->getLogger();
    self::assertInstanceOf(ErrorLogLogger::class, $cXLogger);
    $logger = new ErrorLogLogger();
    $cubex->setLogger($logger);
    self::assertEquals($logger, $cubex->getLogger());
  }

  public function testInstance()
  {
    $this->_cubex();
    self::assertNull(Cubex::instance());
    $cubex = new Cubex(__DIR__, null, true);
    self::assertEquals($cubex, Cubex::instance());
    Cubex::destroyGlobalInstance();
    self::assertNull(Cubex::instance());
  }

  /**
   * @throws Throwable
   */
  public function testContextConfig()
  {
    $cubex = new Cubex(__DIR__, null, false);
    self::assertTrue($cubex->getContext()->config()->has('testing'));
    self::assertEquals('value1', $cubex->getContext()->config()->getItem('testing', 'key_one'));

    $cubex = new Cubex('missing_directory 1', null, false);
    self::assertEmpty($cubex->getContext()->config()->getSections());
    self::assertInstanceOf(ConfigProviderInterface::class, $cubex->getContext()->config());
  }

  /**
   * @throws Throwable
   */
  public function testHandle()
  {
    $cubex = $this->_cubex();
    $router = new Router();
    $router->onPath('/', new FuncHandler(function () { return new TestResponse('All OK'); }));
    /** @var TestResponse $response */
    $response = $cubex->handle($router);
    self::assertInstanceOf(TestResponse::class, $response);
    self::assertEquals('All OK', $response->getSendResult());
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
    $router->onPath(
      '/',
      new FuncHandler(
        function () {
          return new TestResponse('All OK');
        }
      )
    );
    $cubex->listen(
      HandleCompleteEvent::class,
      function () { throw new Exception("Complete Exception", 500); }
    );
    $cubex->handle($router, true, true, true, true);
    self::assertTrue($logger->hasError("Complete Exception"));

    $this->expectExceptionMessage("Complete Exception");
    $cubex->handle($router, true, false, true, true);
  }

  /**
   * @throws Throwable
   */
  public function testHandleEvents()
  {
    $cubex = $this->_cubex();
    $context = $cubex->getContext();
    $cubex->listen(
      PreExecuteEvent::class,
      function (PreExecuteEvent $e) {
        self::assertInstanceOf(Handler::class, $e->getHandler());
        $e->getContext()->meta()->set('HANDLE_PRE_EXECUTE', true);
      }
    );
    $cubex->listen(
      ResponsePrepareEvent::class,
      function (ResponsePrepareEvent $e) {
        self::assertInstanceOf(Response::class, $e->getResponse());
        $e->getContext()->meta()->set('HANDLE_RESPONSE_PREPARE', true);
      }
    );
    $cubex->listen(
      HandleCompleteEvent::class,
      function (HandleCompleteEvent $e) { $e->getContext()->meta()->set('HANDLE_COMPLETE', true); }
    );

    $router = new Router();
    $router->onPath(
      '/',
      new FuncHandler(
        function () {
          return new Response('All OK');
        }
      )
    );
    $cubex->handle($router, false, true);

    self::assertTrue($context->meta()->has('HANDLE_PRE_EXECUTE'));
    self::assertTrue($context->meta()->has('HANDLE_RESPONSE_PREPARE'));
    self::assertTrue($context->meta()->has('HANDLE_COMPLETE'));
  }

  /**
   * @throws Throwable
   */
  public function testNoHandler()
  {
    $cubex = $this->_cubex();
    $this->expectExceptionMessage(ConditionHandler::ERROR_NO_HANDLER);
    $cubex->handle(new Router(), false, false);
  }

  /**
   * @throws Throwable
   */
  public function testExceptionHandler()
  {
    $cubex = $this->_cubex();
    $response = $cubex->handle(new Router(), false, true);
    self::assertEquals(500, $response->getStatusCode());
    self::assertStringContainsString(ConditionHandler::ERROR_NO_HANDLER, $response->getContent());
  }

  /**
   * @throws Exception
   */
  public function testShutdown()
  {
    $cubex = $this->_cubex();
    $cubex->listen(ShutdownEvent::class, function () { throw new Exception("Shutdown", 500); });
    self::assertTrue($cubex->shutdown(false));
    self::assertFalse($cubex->shutdown(false));
    $cubex = $this->_cubex();

    $destructed = false;
    $cubex->listen(ShutdownEvent::class, function () use (&$destructed) { $destructed = true; });
    $cubex->__destruct();
    self::assertTrue($destructed);

    //throwing
    $cubex = $this->_cubex();
    $cubex->listen(ShutdownEvent::class, function () { throw new Exception("Shutdown", 500); });
    $this->expectExceptionMessage("Shutdown");
    $cubex->shutdown(true);
  }

  /**
   * @param string $env
   * @param array  $throwEnv
   * @param bool   $expect
   *
   * @throws Exception
   *
   * @dataProvider _defaultShutdownProvider
   */
  public function testShutdownDefault(string $env, array $throwEnv, bool $expect)
  {
    $cubex = $this->_cubex();
    $cubex->getContext()->setEnvironment($env);
    $cubex->setThrowEnvironments($throwEnv);
    $cubex->listen(ShutdownEvent::class, function () { throw new Exception("Shutdown", 500); });
    if($expect)
    {
      $this->expectExceptionMessage("Shutdown");
    }
    else
    {
      $this->expectNotToPerformAssertions();
    }
    $cubex->shutdown(null);
  }

  public function _defaultShutdownProvider()
  {
    return [
      // env , throw env , expect
      ['env', [], false],
      ['env', ['env'], true],
    ];
  }

  public function testLog()
  {
    $cubex = new Cubex(__DIR__, null, true);
    $logger = new TestLogger();
    $cubex->setLogger($logger);
    Cubex::log()->error("TEST");
    self::assertTrue($logger->hasError("TEST"));
    self::assertFalse($logger->hasError("NOTHING"));
    Cubex::destroyGlobalInstance();
  }

  public function testGetContext()
  {
    $cubex = $this->_cubex();

    self::assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
    $cubex->removeShared(Context::class);

    $cubex->factory(Context::class, function () { return new Context(); });

    $cubex->removeShared(Context::class);
    $cubex->removeFactory(Context::class);
    self::assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
  }

  public function testCustomContext()
  {
    $cubex = Cubex::withCustomContext(Context::class, __DIR__, null, false);
    self::assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
    self::assertInstanceOf(Context::class, $cubex->getContext());

    $cubex = Cubex::withCustomContext(\Cubex\Context\Context::class, __DIR__, null, false);
    self::assertEquals(__DIR__, $cubex->getContext()->getProjectRoot());
    self::assertInstanceOf(\Cubex\Context\Context::class, $cubex->getContext());
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
    self::assertIsInt($result);
    self::assertEquals(0, $result);
    self::assertStringStartsWith('Cubex Console', $output->fetch());
  }

  /**
   * @throws \Exception
   */
  public function testCliException()
  {
    $cubex = $this->_cubex();
    $cubex->listen(
      ConsolePrepareEvent::class,
      function (ConsolePrepareEvent $event) {
        $event->getConsole()->setCatchExceptions(false);
        $event->getConsole()->add(new TestExceptionCommand());
        $event->getConsole()->getContext()->meta()->set('TestExceptionCommand', true);
        self::assertInstanceOf(InputInterface::class, $event->getInput());
        self::assertInstanceOf(OutputInterface::class, $event->getOutput());
      }
    );
    $cubex->listen(
      ConsoleCreateEvent::class,
      function (ConsoleCreateEvent $event) {
        self::assertInstanceOf(Console::class, $event->getConsole());
      }
    );
    $input = new ArgvInput(['', 'TestExceptionCommand']);
    $output = new BufferedOutput();
    $result = $cubex->cli($input, $output);
    self::assertIsInt($result);
    self::assertEquals(1, $result);
    self::assertStringContainsString('GENERIC EXCEPTION', $output->fetch());
  }

  public function testContextFactory()
  {
    $_ENV[Cubex::_ENV_VAR] = Context::ENV_DEV;
    $cubex = $this->_cubex();
    $ctx = $cubex->getContext();
    self::assertEquals(Context::ENV_DEV, $ctx->getEnvironment());

    $cubex = $this->_cubex();
    $factory = function () {
      $ctx = new Context(Request::createFromGlobals());
      $ctx->meta()->set("abc", 'xyz');
      return $ctx;
    };
    $cubex->factory(Context::class, $factory);
    $ctx = $cubex->getContext();
    self::assertEquals("xyz", $ctx->meta()->get('abc'));
  }

  public function testExceptionThrowing()
  {
    $cubex = $this->_cubex();
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_LOCAL)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_DEV)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_PHPUNIT)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_QA)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_UAT)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_STAGE)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_PROD)));

    $cubex = $this->_cubex()->setThrowEnvironments([]);
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_LOCAL)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_DEV)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_PHPUNIT)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_QA)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_UAT)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_STAGE)));
    self::assertTrue($cubex->willCatchExceptions(Context::create('', Context::ENV_PROD)));

    $cubex = $this->_cubex()->setThrowEnvironments(
      [
        Context::ENV_LOCAL,
        Context::ENV_DEV,
        Context::ENV_PHPUNIT,
        Context::ENV_QA,
        Context::ENV_UAT,
        Context::ENV_STAGE,
        Context::ENV_PROD,
      ]
    );
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_LOCAL)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_DEV)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_PHPUNIT)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_QA)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_UAT)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_STAGE)));
    self::assertFalse($cubex->willCatchExceptions(Context::create('', Context::ENV_PROD)));
  }

  public function testFromContext()
  {
    $cubex = $this->_cubex();
    $ctx = $cubex->getContext();
    self::assertSame($cubex, Cubex::fromContext($ctx));
    self::assertNull(Cubex::fromContext(new Context()));

    $cubex = new Cubex('', null, true);
    self::assertSame($cubex, Cubex::fromContext(new Context()));
    Cubex::destroyGlobalInstance();
    self::assertNull(Cubex::fromContext(new Context()));
  }
}
