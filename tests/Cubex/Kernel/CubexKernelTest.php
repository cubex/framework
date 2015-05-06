<?php
namespace CubexTest\Cubex\Kernel;

use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\Http\Request;
use Cubex\Http\Response;
use Cubex\ICubexAware;
use Cubex\Kernel\CubexKernel;
use Cubex\Responses\Error404Response;
use Cubex\Routing\Route;
use Illuminate\Support\Contracts\RenderableInterface;
use namespaced\CubexProject;
use Packaged\Config\Provider\Test\TestConfigProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CubexKernelTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @param string $defaultAction
   *
   * @return CubexKernel
   */
  public function getKernel($defaultAction = 'abc')
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMock('\Cubex\Kernel\CubexKernel', ['defaultAction']);
    $kernel->expects($this->any())->method("defaultAction")->will(
      $this->returnValue(($defaultAction))
    );
    $kernel->setCubex($cubex);
    return $kernel;
  }

  public function testConfiguration()
  {
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    /**
     * @var $kernel CubexKernel
     */
    $config = new TestConfigProvider();
    $config->addItem('test', 'entry', 'cube');
    $cubex = new Cubex();
    $cubex->configure($config);
    $kernel->setCubex($cubex);
    $this->assertEquals('cube', $kernel->getConfigItem('test', 'entry', false));
    $this->assertEquals(false, $kernel->getConfigItem('test', 'ab', false));
  }

  public function testCubexAwareGetNull()
  {
    $this->setExpectedException('RuntimeException');
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    $kernel->getCubex();
  }

  public function testGetDefaultAction()
  {
    //Ensure default action is null to avoid conflicts with user projects
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    $this->assertNull($kernel->defaultAction());
  }

  public function testGetRequest()
  {
    $kernel = new RequestGetTest();
    $cubex = new Cubex();
    $request = Request::createFromGlobals();
    $cubex->instance('request', $request);
    $kernel->setCubex($cubex);
    $this->assertInstanceOf('\Cubex\Http\Request', $kernel->getRequest());
    $this->assertSame($request, $kernel->getRequest());
  }

  public function testCubexAwareSetGet()
  {
    $kernel = $this->getKernel();
    $cubex = new Cubex();
    $kernel->setCubex($cubex);
    $this->assertSame($kernel->getCubex(), $cubex);
  }

  public function testHandle()
  {
    $request = Request::createFromGlobals();
    $kernel = $this->getKernel();
    $resp = $kernel->handle($request, Cubex::MASTER_REQUEST, false);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
  }

  public function testHeaders()
  {
    $request = Request::createFromGlobals();
    $kernel = $this->getKernel(Response::create('abc'));
    $resp = $kernel->handle($request, Cubex::MASTER_REQUEST, false);
    if($resp instanceof Response)
    {
      $resp->setCubexHeaders();
    }
    $this->assertTrue($resp->headers->has('X-Execution-Time'));
    $this->assertTrue($resp->headers->has('X-Call-Time'));
  }

  public function test404Handle()
  {
    $this->setExpectedException(
      'Exception',
      "The processed route did not yield a valid response",
      404
    );

    $request = Request::createFromGlobals();
    $kernel = new ResultNotFoundTest();
    $kernel->setCubex($this->getKernel()->getCubex());
    $kernel->handle($request, Cubex::MASTER_REQUEST, false);
  }

  public function testHandleWithRoutes()
  {
    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/hello/world');
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMock(
      '\Cubex\Kernel\CubexKernel',
      ['getRoutes', 'resp']
    );
    $kernel->expects($this->any())->method("getRoutes")->will(
      $this->returnValue(
        [
          'hello/world' => 'resp'
        ]
      )
    );
    $kernel->expects($this->any())->method("resp")->will(
      $this->returnValue("respdata")
    );
    $kernel->setCubex($cubex);
    $resp = $kernel->handle($request, Cubex::MASTER_REQUEST, false);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
    $this->assertEquals("respdata", $resp->getContent());
  }

  public function testHandleWithException()
  {
    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/hello/world');
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMock(
      '\Cubex\Kernel\CubexKernel',
      ['getRoutes', 'resp']
    );
    $kernel->expects($this->any())->method("getRoutes")->will(
      $this->returnValue(
        [
          'hello/world' => 'resp'
        ]
      )
    );
    $kernel->expects($this->any())->method("resp")->will(
      $this->throwException(new \Exception("Mocked Exception"))
    );
    $kernel->setCubex($cubex);
    $resp = $kernel->handle($request, Cubex::MASTER_REQUEST, true);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
    $this->assertContains("Mocked Exception", $resp->getContent());
  }

  public function testHandleInvalidResponse()
  {
    $request = Request::createFromGlobals();
    $kernel = $this->getKernel(null);
    $resp = $kernel->handle($request, Cubex::MASTER_REQUEST, true);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
  }

  public function testThrowsExceptionWithNoRouter()
  {
    $this->setExpectedException("RuntimeException", "No IRouter located");
    $request = Request::createFromGlobals();
    $kernel = $this->getKernel();
    $kernel->getCubex()->instance('\Cubex\Routing\IRouter', new \stdClass());
    $kernel->handle($request, Cubex::MASTER_REQUEST, false);
  }

  public function testAttemptCallable()
  {
    $kernel = $this->getKernel();
    $this->assertTrue(
      is_callable($kernel->attemptCallable('\Cubex\Routing\Route,getValue'))
    );
    $this->assertTrue(
      is_callable($kernel->attemptCallable('\Cubex\Routing\Route@getValue'))
    );
    $this->assertFalse(
      is_callable($kernel->attemptCallable('\Cubex\Routing\Route@'))
    );
    $this->assertFalse(
      is_callable($kernel->attemptCallable('\Cubex\Routing\Route'))
    );
    $this->assertFalse(
      is_callable($kernel->attemptCallable('randomString'))
    );
  }

  public function testAttemptMethod()
  {
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMockBuilder('\Cubex\Kernel\CubexKernel')
      ->setMethods(
        [
          "ajaxGetUser",
          'postGetUser',
          'patchGetUser',
          'getUser',
          'get_account',
          'renderIndex',
          'actionHomepage',
          'renderGettingStarted',
          'downloadActions',
          'defaultAction'
        ]
      )
      ->getMock();

    $request = Request::createFromGlobals();

    $this->assertEquals(
      "getUser",
      $kernel->attemptMethod("getUser", $request)
    );

    $this->assertEquals(
      "get_account",
      $kernel->attemptMethod("get_account", $request)
    );

    $this->assertEquals(
      "renderGettingStarted",
      $kernel->attemptMethod("getting-started", $request)
    );

    $this->assertEquals(
      "downloadActions",
      $kernel->attemptMethod("download-actions", $request)
    );

    $this->assertEquals(
      "renderIndex",
      $kernel->attemptMethod("index", $request)
    );

    $this->assertEquals(
      "actionHomepage",
      $kernel->attemptMethod("homepage", $request)
    );

    $this->assertEquals(
      "defaultAction",
      $kernel->attemptMethod("defaultAction", $request)
    );

    $request->setMethod('POST');

    $this->assertEquals(
      "postGetUser",
      $kernel->attemptMethod("getUser", $request)
    );

    $request->setMethod('PATCH');

    $this->assertEquals(
      "patchGetUser",
      $kernel->attemptMethod("getUser", $request)
    );

    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $this->assertEquals(
      "ajaxGetUser",
      $kernel->attemptMethod("getUser", $request)
    );

    $this->assertNull($kernel->attemptMethod("getMissingMethod", $request));
  }

  /**
   * @param      $route
   * @param null $expectUrl
   * @param null $expectCode
   * @param null $request
   *
   * @dataProvider urlProvider
   */
  public function testAttemptUrl(
    $route, $expectUrl = null, $expectCode = null, $request = null
  )
  {
    $kernel = $this->getKernel();
    $result = $kernel->attemptUrl($route, $request);

    if($expectUrl === null)
    {
      $this->assertNull($result);
    }
    else
    {
      $this->assertEquals($expectUrl, $result['url']);
      if($expectCode !== null)
      {
        $this->assertEquals($expectCode, $result['code']);
      }
    }
  }

  public function urlProvider()
  {
    $slashedRequest = Request::createFromGlobals();
    $slashedRequest->server->set('REQUEST_URI', '/tester/');

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/tester');

    return [
      ["invalid", null],
      ["invalid/url", null],
      ["#@home", "home", 302],
      ["#@home", "home"],
      ["#@home", "home", null, $request],
      ["#@home", "../home", null, $slashedRequest],
      ["#@/home", "/home"],
      ["http://google.com", "http://google.com"],
      ["@301!http://google.com", "http://google.com", 301],
      ["@400!http://google.com", "http://google.com", 400],
    ];
  }

  /**
   * @param $response
   * @param $captured
   * @param $expected
   *
   * @dataProvider handleResponseProvider
   */
  public function testHandleResponse($response, $captured, $expected)
  {
    $kernel = $this->getKernel();
    $final = $kernel->handleResponse($response, $captured);
    $this->assertInstanceOf('\Cubex\Http\Response', $final);
    $this->assertEquals($expected, $final->getContent());
  }

  public function handleResponseProvider()
  {
    $resp = new Response("construct");
    return [
      ["response", "capture", "response"],
      ["", "capture", ""],
      [null, "capture", "capture"],
      [true, "capture", 'true'],
      [false, "capture", 'false'],
      [$resp, "capture", "construct"],
    ];
  }

  public function testAutoRoute()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CubexKernel $kernel
     */
    $kernel = $this->getMock(
      '\Cubex\Kernel\CubexKernel',
      ['renderCubexed', 'cubex']
    );
    $kernel->expects($this->any())->method("renderCubexed")->will(
      $this->returnValue(("cubexed"))
    );
    $kernel->expects($this->any())->method("cubex")->will(
      $this->returnValue(("cubex"))
    );

    $kernel->setCubex($cubex);
    /**
     * @var Request $request
     */

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/tester');
    $this->assertInstanceOf(
      '\Cubex\Responses\Error404Response',
      $kernel->handle($request)
    );

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/cubexed/tester');
    $this->assertEquals("cubexed", $kernel->handle($request)->getContent());

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/cubex/tester');
    $this->assertEquals("cubex", $kernel->handle($request)->getContent());
  }

  /**
   * @param CubexKernel $kernel
   * @param             $routeData
   * @param             $expect
   * @param null        $exception
   *
   * @dataProvider executeRouteProvider
   */
  public function testExecuteRoute(
    CubexKernel $kernel, $routeData, $expect, $exception = null
  )
  {
    $route = new Route();
    $route->createFromRaw($routeData);

    $request = Request::createFromGlobals();
    $type = Cubex::MASTER_REQUEST;

    $catch = $exception === null;

    if(!$catch)
    {
      $this->setExpectedException('Exception', $exception);
    }

    $result = $kernel->executeRoute($route, $request, $type, $catch);

    if($expect instanceof RedirectResponse)
    {
      $this->assertInstanceOf(
        '\Symfony\Component\HttpFoundation\RedirectResponse',
        $result
      );
      /**
       * @var RedirectResponse $result
       */
      $this->assertEquals($expect->getTargetUrl(), $result->getTargetUrl());
    }
    else if($expect instanceof \Symfony\Component\HttpFoundation\Response)
    {
      $this->assertInstanceOf(
        '\Symfony\Component\HttpFoundation\Response',
        $result
      );
      $this->assertEquals($expect->getContent(), $result->getContent());
    }
    else if($result instanceof \Symfony\Component\HttpFoundation\Response)
    {
      $this->assertEquals($expect, $result->getContent());
    }
    else
    {
      $this->assertEquals($expect, $result);
    }
  }

  public function executeRouteProvider()
  {
    $cubex = new Cubex();
    $response = new Response("hey");

    $toString = $this->getMock('stdClass', ['__toString']);
    $toString->expects($this->any())->method("__toString")->will(
      $this->returnValue("test")
    );

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CubexKernel $kernel
     */
    $kernel = $this->getMock(
      '\Cubex\Kernel\CubexKernel',
      ['actionIn', 'handle']
    );
    $kernel->expects($this->any())->method("actionIn")->will(
      $this->returnValue(("method"))
    );
    $kernel->expects($this->any())->method("handle")->will(
      $this->returnValue(($response))
    );
    $kernel->setCubex($cubex);

    $callable = function ()
    {
      return "testCallable";
    };

    $namespaceKernel = new CubexProject();
    $namespaceKernel->setCubex($cubex);

    $dataO = new \stdClass();
    $dataO->name = "Brooke";

    return [
      [$kernel, null, null],
      [$kernel, $response, $response],
      [$kernel, $kernel, $response],
      [
        $kernel,
        '\Cubex\Responses\Error404Response',
        new Error404Response()
      ],
      [$kernel, 'stdClass', null],
      [$kernel, $callable, "testCallable"],
      [$kernel, $dataO, '{"name":"Brooke"}'],
      [$kernel, new RenderableTest(), "renderable"],
      [$namespaceKernel, 'Sub\SubRoutable', "namespaced sub"],
      [$namespaceKernel, '\TheRoutable', "namespaced"],
      [
        $namespaceKernel,
        '\NoSuchClass',
        null,
        "Your route provides an invalid class '\\NoSuchClass'"
      ],
      [
        $namespaceKernel,
        '\NoSuchClass',
        null,
        null
      ],
      [
        $kernel,
        'http://google.com',
        new RedirectResponse(
          'http://google.com'
        )
      ],
      [$kernel, 'actionIn', new Response("method")],
      [
        $kernel,
        '\Symfony\Component\HttpFoundation\Request,createFromGlobals',
        \Symfony\Component\HttpFoundation\Request::createFromGlobals()
      ],
      [$kernel, $toString, "test"],
    ];
  }

  public function testSubClassRouting()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());

    $kernel = new AppTest();
    $kernel->setCubex($cubex);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', 'boiler');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('boiled', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', 'kernelBoiler');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('Kernel Boiler Response', $result->getContent());

    $kernel = new CubexProject();
    $kernel->setCubex($cubex);
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random/tags/pet');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test tag pet', $result->getContent());

    $kernel = new CubexProject();
    $kernel->setCubex($cubex);
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random/blue/mist');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('blue mist', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test extension', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random/arg/test');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('arg test', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test application', $result->getContent());
  }

  public function testDefaultRoute()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());

    $kernel = new CubexProject();
    $kernel->setCubex($cubex);
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test default action', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/test-sub-route');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test sub route', $result->getContent());
  }

  public function testRoutingParameters()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());

    $kernel = new CubexProject();
    $kernel->setCubex($cubex);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/pathnum/12345');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('12345', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/pathnum/abcdef');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test default action', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/pathalpha/abcdef');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('abcdef', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/pathalpha/12345');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('test default action', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/pathall/123abc/more');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('123abc/more', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/default/path/123abc/more');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('123abc', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/droute');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('processed: droute', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/droute/path/test');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('processed: droute', $result->getContent());
  }

  public function invalidRoute()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());

    $kernel = new AppTest();
    $kernel->setCubex($cubex);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', 'failureClass');
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertNull($result);
  }

  public function testManualRouting()
  {
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());

    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMock('\Cubex\Kernel\CubexKernel', ['getRoutes']);
    $kernel->expects($this->any())->method("getRoutes")->will(
      $this->returnValue(
        ['manual-route' => '\namespaced\sub\ManualRouteExtension']
      )
    );
    $kernel->setCubex($cubex);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set(
      'REQUEST_URI',
      '/manual-route/cb/helloworld'
    );
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('callback route', $result->getContent());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set(
      'REQUEST_URI',
      '/manual-route/first-path/123/show/456'
    );
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals(
      'showing manual route for 123 456',
      $result->getContent()
    );

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set(
      'REQUEST_URI',
      '/manual-route/second-path/987/show/654'
    );
    $result = $kernel->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals(
      'showing manual route for 987 654',
      $result->getContent()
    );
  }

  public function testCanProcess()
  {
    $kernel = new KernelAuthTest();
    $result = $kernel->handle(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertEquals('Please Login', $result->getContent());

    $kernel = new KernelAuthTest(false);
    $result = $kernel->handle(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains(
      'Error 403 - Access Forbidden',
      $result->getContent()
    );
    $this->assertEquals(403, $result->getStatusCode());
  }

  public function testCubexify()
  {
    $boiler = new BoilerTest();
    $kernel = $this->getKernel();
    $cubex = $kernel->getCubex();
    $kernel->bindCubex($boiler);
    $this->assertSame($cubex, $boiler->getCubex());
  }

  /**
   * @param $uri
   * @param $route
   *
   * @dataProvider baseRoutesProvider
   *
   * @link         https://github.com/cubex/framework/issues/2
   */
  public function testBaseRoutes($uri, $route)
  {
    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_URI', $uri);
    $cubex = new Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    /**
     * @var CubexKernel|\PHPUnit_Framework_MockObject_MockObject $kernel
     */
    $kernel = $this->getMock(
      '\Cubex\Kernel\CubexKernel',
      ['getRoutes', 'resp']
    );
    $kernel->expects($this->any())->method("getRoutes")->will(
      $this->returnValue(
        [
          $route => 'resp'
        ]
      )
    );
    $kernel->expects($this->any())->method("resp")->will(
      $this->returnValue("respdata")
    );
    $kernel->setCubex($cubex);
    $resp = $kernel->handle($request, Cubex::MASTER_REQUEST, false);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
    $this->assertEquals("respdata", $resp->getContent());
  }

  public function baseRoutesProvider()
  {
    return [
      ['', ''],
      ['/', '/'],
      ['', '/'],
      ['/', ''],
      ['/r', 'r'],
      ['r', '/r'],
    ];
  }

  public function testRouteData()
  {
    $kernel = new RouteDataTest();
    $this->assertInternalType('array', $kernel->getRouteData());
    $this->assertArrayHasKey('one', $kernel->getRouteData());
    $this->assertCount(2, $kernel->getRouteData());
    $this->assertNull($kernel->getRouteData('three'));
    $this->assertEquals('test', $kernel->getRouteData('three', 'test'));
    $this->assertEquals('two', $kernel->getRouteData('one'));
    $this->assertEquals('b', $kernel->getRouteData('a', 'b'));
  }
}

class AppTest extends CubexKernel
{
  public function subRouteTo()
  {
    return ['\%sTest'];
  }
}

class BoilerTest implements ICubexAware
{
  use CubexAwareTrait;

  public function __toString()
  {
    return 'boiled';
  }
}

class RenderableTest
  implements RenderableInterface
{
  public function render()
  {
    return 'renderable';
  }
}

class KernelBoilerTest extends CubexKernel
{
  public function handle(
    \Symfony\Component\HttpFoundation\Request $request,
    $type = self::MASTER_REQUEST, $catch = true
  )
  {
    return new Response('Kernel Boiler Response');
  }
}

class KernelAuthTest extends CubexKernel
{
  protected $_authResponse;

  public function __construct($response = null)
  {
    $this->_authResponse = $response;
  }

  public function canProcess()
  {
    if($this->_authResponse !== null)
    {
      return $this->_authResponse;
    }
    return new Response('Please Login', 200);
  }
}

class RequestGetTest extends CubexKernel
{
  public function getRequest()
  {
    return $this->_getRequest();
  }
}

class ResultNotFoundTest extends CubexKernel
{
  public function defaultAction()
  {
    return null;
  }
}

class RouteDataTest extends CubexKernel
{
  protected $_processParams = [
    'one' => 'two',
    'a'   => 'b'
  ];

  public function getRouteData($key = null, $default = null)
  {
    return $this->_getRouteData($key, $default);
  }
}
