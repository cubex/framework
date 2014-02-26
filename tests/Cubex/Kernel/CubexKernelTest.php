<?php

class CubexKernelTest extends PHPUnit_Framework_TestCase
{
  /**
   * @return \Cubex\Kernel\CubexKernel
   */
  public function getKernel($defaultAction = 'abc')
  {
    $cubex = new \Cubex\Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
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
     * @var $kernel \Cubex\Kernel\CubexKernel
     */
    $config = new \Packaged\Config\Provider\Test\TestConfigProvider();
    $config->addItem('test', 'entry', 'cube');
    $cubex = new \Cubex\Cubex();
    $cubex->configure($config);
    $kernel->setCubex($cubex);
    $this->assertEquals('cube', $kernel->getConfigItem('test', 'entry', false));
    $this->assertEquals(false, $kernel->getConfigItem('test', 'ab', false));
  }

  public function testCubexAwareGetNull()
  {
    $this->setExpectedException('RuntimeException');
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    $kernel->getCubex();
  }

  public function testGetDefaultAction()
  {
    //Ensure default action is null to avoid conflicts with user projects
    $kernel = $this->getMockForAbstractClass('\Cubex\Kernel\CubexKernel');
    $this->assertNull($kernel->defaultAction());
  }

  public function testCubexAwareSetGet()
  {
    $kernel = $this->getKernel();
    $cubex  = new \Cubex\Cubex();
    $kernel->setCubex($cubex);
    $this->assertSame($kernel->getCubex(), $cubex);
  }

  public function testHandle()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $kernel  = $this->getKernel();
    $resp    = $kernel->handle($request, \Cubex\Cubex::MASTER_REQUEST, false);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
  }

  public function testHandleWithRoutes()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/hello/world');
    $cubex = new \Cubex\Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
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
    $resp = $kernel->handle($request, \Cubex\Cubex::MASTER_REQUEST, false);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
    $this->assertContains("respdata", (string)$resp);
  }

  public function testHandleInvalidResponse()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $kernel  = $this->getKernel(null);
    $resp    = $kernel->handle($request, \Cubex\Cubex::MASTER_REQUEST, true);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $resp
    );
  }

  public function testThrowsExceptionWithNoRouter()
  {
    $this->setExpectedException("RuntimeException", "No IRouter located");
    $request = \Cubex\Http\Request::createFromGlobals();
    $kernel  = $this->getKernel();
    $kernel->getCubex()->instance('\Cubex\Routing\IRouter', new stdClass());
    $kernel->handle($request, \Cubex\Cubex::MASTER_REQUEST, false);
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
    $kernel = $this->getMockBuilder('\Cubex\Kernel\CubexKernel')
      ->setMethods(
        [
          "ajaxGetUser",
          'postGetUser',
          'getUser',
          'renderIndex',
          'actionHomepage',
          'defaultAction'
        ]
      )
      ->getMock();

    $request = \Cubex\Http\Request::createFromGlobals();

    $this->assertEquals(
      "getUser",
      $kernel->attemptMethod("getUser", $request)
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

    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $this->assertEquals(
      "ajaxGetUser",
      $kernel->attemptMethod("getUser", $request)
    );

    $this->assertNull($kernel->attemptMethod("getMissingMethod", $request));
  }

  /**
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
    $slashedRequest = \Cubex\Http\Request::createFromGlobals();
    $slashedRequest->server->set('REQUEST_URI', '/tester/');

    $request = \Cubex\Http\Request::createFromGlobals();
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
   * @dataProvider handleResponseProvider
   */
  public function testHandleResponse($response, $captured, $expected)
  {
    $kernel = $this->getKernel();
    $final  = $kernel->handleResponse($response, $captured);
    $this->assertInstanceOf('\Cubex\Http\Response', $final);
    $this->assertEquals($expected, $final->getContent());
  }

  public function handleResponseProvider()
  {
    $resp = new \Cubex\Http\Response("construct");
    return [
      ["response", "capture", "response"],
      ["", "capture", "capture"],
      [$resp, "capture", "construct"],
    ];
  }

  public function testAutoRoute()
  {
    $cubex = new \Cubex\Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
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
    /**
     * @var PHPUnit_Framework_MockObject_MockObject|\Cubex\Kernel\CubexKernel $kernel
     */

    $kernel->setCubex($cubex);
    /**
     * @var \Cubex\Http\Request $request
     */

    $request = \Cubex\Http\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/tester');
    $this->assertNull($kernel->autoRoute($request));

    $request = \Cubex\Http\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/cubexed/tester');
    $this->assertContains("cubexed", (string)$kernel->autoRoute($request));

    $request = \Cubex\Http\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/cubex/tester');
    $this->assertContains("cubex", (string)$kernel->autoRoute($request));
  }

  /**
   * @dataProvider executeRouteProvider
   */
  public function testExecuteRoute(
    $kernel, $routeData, $expect, $exception = null
  )
  {
    $route = new \Cubex\Routing\Route();
    $route->createFromRaw($routeData);

    $request = \Cubex\Http\Request::createFromGlobals();
    $type    = \Cubex\Cubex::MASTER_REQUEST;

    if($exception !== null)
    {
      $this->setExpectedException('Exception', $exception);
    }

    $result = $kernel->executeRoute($route, $request, $type, true);

    if($expect instanceof \Symfony\Component\HttpFoundation\RedirectResponse)
    {
      $this->assertInstanceOf(
        '\Symfony\Component\HttpFoundation\RedirectResponse',
        $result
      );
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
    else
    {
      $this->assertEquals($expect, $result);
    }
  }

  public function executeRouteProvider()
  {
    $cubex    = new \Cubex\Cubex();
    $response = new \Cubex\Http\Response("hey");

    $toString = $this->getMock('stdClass', ['__toString']);
    $toString->expects($this->any())->method("__toString")->will(
      $this->returnValue("test")
    );

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

    $namespaceKernel = new \namespaced\CubexProject();
    $namespaceKernel->setCubex($cubex);

    return [
      [$kernel, null, null],
      [$kernel, $response, $response],
      [$kernel, $kernel, $response],
      [
        $kernel,
        '\Cubex\Responses\Error404Response',
        new \Cubex\Responses\Error404Response()
      ],
      [$kernel, 'stdClass', null],
      [$kernel, $callable, "testCallable"],
      [$namespaceKernel, 'Sub\SubRoutable', "namespaced sub"],
      [$namespaceKernel, '\TheRoutable', "namespaced"],
      [
        $namespaceKernel,
        '\NoSuchClass',
        null,
        "Your route provides an invalid class '\\NoSuchClass'"
      ],
      [
        $kernel,
        'http://google.com',
        new \Symfony\Component\HttpFoundation\RedirectResponse(
          'http://google.com'
        )
      ],
      [$kernel, 'actionIn', new \Cubex\Http\Response("method")],
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
    $cubex = new \Cubex\Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());

    $kernel = $this->getMock('\Cubex\Kernel\CubexKernel', ['subRouteTo']);
    $kernel->expects($this->any())->method("subRouteTo")->will(
      $this->returnValue(['%sTest'])
    );
    $kernel->setCubex($cubex);

    $result = $kernel->attemptSubClass(
      'boiler',
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('boiled', (string)$result);

    $result = $kernel->attemptSubClass(
      'kernelBoiler',
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('Kernel Boiler Response', (string)$result);

    $result = $kernel->attemptSubClass(
      'failureClass',
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertNull($result);

    $kernel = new \namespaced\CubexProject();
    $kernel->setCubex($cubex);
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random/tags/pet');
    $result = $kernel->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('test tag pet', (string)$result);

    $kernel = new \namespaced\CubexProject();
    $kernel->setCubex($cubex);
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random/blue/mist');
    $result = $kernel->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('blue mist', (string)$result);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test/random');
    $result = $kernel->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('test extension', (string)$result);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->server->set('REQUEST_URI', '/test');
    $result = $kernel->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('test application', (string)$result);
  }
}

class BoilerTest implements \Cubex\ICubexAware
{
  use \Cubex\CubexAwareTrait;

  public function __toString()
  {
    return 'boiled';
  }
}

class KernelBoilerTest extends \Cubex\Kernel\CubexKernel
{
  public function handle(
    \Symfony\Component\HttpFoundation\Request $request,
    $type = self::MASTER_REQUEST, $catch = true
  )
  {
    return new \Cubex\Http\Response('Kernel Boiler Response');
  }
}
