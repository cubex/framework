<?php
namespace CubexTest\Cubex;

use Cubex\Cubex;
use Cubex\CubexException;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Packaged\Config\Provider\Test\TestConfigProvider;

class CubexTest extends \PHPUnit_Framework_TestCase
{
  public function sampleProjectPath()
  {
    return dirname(__DIR__);
  }

  public function sampleProjectCubex()
  {
    return new Cubex($this->sampleProjectPath());
  }

  public function testConstruct()
  {
    $cubex = new Cubex(__DIR__);
    $this->assertEquals(__DIR__, $cubex->getDocRoot());
    $this->assertEquals(dirname(__DIR__), $cubex->getProjectRoot());
  }

  public function testDoubleBoot()
  {
    $cubex = new Cubex(__DIR__);
    $cubex->boot();
    $sm = $cubex->make('service.manager');
    $cubex->boot();
    $this->assertSame($sm, $cubex->make('service.manager'));
  }

  public function testExceptionWhenNoDocRootDefined()
  {
    $request = Request::createFromGlobals();
    $cubex = new Cubex();
    $this->setExpectedException('RuntimeException');
    $cubex->handle($request, Cubex::MASTER_REQUEST, false);
  }

  public function testConfiguration()
  {
    $config = new TestConfigProvider();
    $cubex = new Cubex();
    $this->assertEquals(null, $cubex->getConfiguration());
    $this->assertEquals($cubex, $cubex->configure($config));
    $this->assertEquals($config, $cubex->getConfiguration());
  }

  public function testFlags()
  {
    $cubex = new Cubex();
    $this->assertFalse($cubex->hasFlag('test'));
    $cubex->setFlag('test');
    $this->assertTrue($cubex->hasFlag('test'));
    $cubex->setFlag('test', false);
    $this->assertFalse($cubex->hasFlag('test'));

    $cubex->setFlag('test');
    $cubex->unsetFlag('test');
    $this->assertFalse($cubex->hasFlag('test'));
  }

  public function testExceptions()
  {
    $cubex = new Cubex();
    $exception = new \Exception("Test Exception", 345);
    $resp = $cubex->exceptionResponse($exception);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
    $this->assertContains('An uncaught exception was thrown', (string)$resp);
    $this->assertContains('Test Exception', (string)$resp);
    $this->assertContains('345', (string)$resp);

    $exception = CubexException::debugException("msg", 123, 'solution');
    $resp = $cubex->exceptionResponse($exception);
    $this->assertContains('msg', (string)$resp);
    $this->assertContains('123', (string)$resp);
    $this->assertContains('solution', (string)$resp);
  }

  public function testHandle()
  {
    $request = Request::createFromGlobals();
    $cubex = $this->sampleProjectCubex();
    $cubex->configure(new TestConfigProvider());
    $resp = $cubex->handle($request);
    $this->assertTrue($cubex->bound('request'));
    $this->assertInstanceOf('\Cubex\Http\Request', $cubex->make('request'));
    $this->assertInstanceOf('\Cubex\Http\Response', $resp);
  }

  public function testFavicon()
  {
    $request = Request::create('/favicon.ico');
    $cubex = $this->sampleProjectCubex();
    $cubex->configure(new TestConfigProvider());
    $resp = $cubex->handle($request);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\BinaryFileResponse',
      $resp
    );
  }

  public function testInvalidRequest()
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $cubex = $this->sampleProjectCubex();

    $resp = $cubex->handle($request);
    $this->assertContains('An uncaught exception was thrown', (string)$resp);
    $this->assertContains('You must use a \Cubex\Http\Request', (string)$resp);

    $this->setExpectedException(
      '\InvalidArgumentException',
      'You must use a \Cubex\Http\Request'
    );
    $cubex->handle(
      $request,
      Cubex::MASTER_REQUEST,
      false
    );

    $resp = $cubex->handle($request);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
    $this->assertContains('Uncaught Exception', (string)$resp);
    $this->assertContains('You must use a \Cubex\Http\Request', (string)$resp);
  }

  public function testTerminate()
  {
    $cubex = $this->sampleProjectCubex();
    $cubex->terminate(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      new \Symfony\Component\HttpFoundation\Response('Hello World')
    );
  }

  public function testNoKernelProvidedException()
  {
    $this->setExpectedException(
      "RuntimeException",
      "No Cubex Kernel has been configured"
    );
    $cubex = new Cubex(__DIR__);
    $cubex->instance('\Cubex\Kernel\CubexKernel', new \stdClass());
    $request = Request::createFromGlobals();
    $cubex->handle($request, Cubex::MASTER_REQUEST, false);
  }

  public function testBrokenKernelProvidedException()
  {
    $cubex = $this->sampleProjectCubex();
    $kernel = $this->getMock('\Cubex\Kernel\CubexKernel');
    $kernel->expects($this->any())
      ->method("handle")
      ->will($this->returnValue((null)));
    $kernel->setCubex($cubex);

    $request = Request::createFromGlobals();
    $cubex->instance('\Cubex\Kernel\CubexKernel', $kernel);
    $this->assertNull($kernel->handle($request));
    $this->setExpectedException(
      '\Cubex\CubexException',
      "A valid response was not generated by the default kernel",
      500
    );
    $cubex->handle($request, Cubex::MASTER_REQUEST, false);
  }

  /**
   * @requires extension zlib
   */
  public function testResponse()
  {
    $request = Request::createFromGlobals();

    $config = new TestConfigProvider();

    $cubex = new Cubex(__DIR__);
    $cubex->configure($config);

    $kernel = $this->getMock('\Cubex\Kernel\CubexKernel');
    $kernel->expects($this->any())
      ->method("handle")
      ->will($this->returnValue((new Response("Good Data"))));
    $kernel->setCubex($cubex);

    $cubex->instance('\Cubex\Kernel\CubexKernel', $kernel);
    $cubex->handle($request, Cubex::MASTER_REQUEST, false);
  }

  public function testConfigure()
  {
    $config = new TestConfigProvider();
    $config->addItem("kernel", "project", 'Project');

    $cubex = new Cubex();
    $cubex->configure($config);
    $this->assertSame($config, $cubex->getConfiguration());
  }

  public function testEnv()
  {
    putenv('CUBEX_ENV');

    $cubex = new Cubex();
    $this->assertEquals('local', $cubex->env());

    $cubex->setEnv('rand');
    $this->assertEquals('rand', $cubex->env());

    $_ENV['CUBEX_ENV'] = 'prod';
    $cubex = new Cubex();
    $this->assertEquals('prod', $cubex->env());

    putenv('CUBEX_ENV=stage');
    $cubex = new Cubex();
    $this->assertEquals('stage', $cubex->env());

    putenv('CUBEX_ENV');
  }

  /**
   * @param \Exception $e
   * @param            $contains
   *
   * @dataProvider exceptionProvider
   */
  public function testExceptionAsString(\Exception $e, $contains)
  {
    if(is_array($contains))
    {
      foreach($contains as $contain)
      {
        $this->assertContains($contain, Cubex::exceptionAsString($e));
      }
    }
    else
    {
      $this->assertContains($contains, Cubex::exceptionAsString($e));
    }
  }

  public function exceptionProvider()
  {
    return [
      [
        CubexException::debugException('Broken', 404, 'debug'),
        ['Broken</h2>', '(404)', 'debug</div>']
      ],
      [
        CubexException::debugException('', 0, ['a' => 'b']),
        print_r(['a' => 'b'], true)
      ],
      [
        CubexException::debugException('', 0, false),
        'bool(false)'
      ],
      [
        CubexException::debugException('', 0, 23),
        'int(23)'
      ]
    ];
  }
}
