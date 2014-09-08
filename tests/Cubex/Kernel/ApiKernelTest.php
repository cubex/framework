<?php

class ApiKernelTestInternal extends InternalCubexTestCase
{
  /**
   * @param $path
   * @param $expect
   * @param $errMsg
   * @param $errNo
   *
   * @dataProvider handleProvider
   */
  public function testHandle($path, $expect, $errMsg, $errNo)
  {
    $request = \Cubex\Http\Request::create($path);
    $kernel  = new ApiTestKernel();
    $cubex   = $this->newCubexInstace();
    $cubex->instance('\Cubex\Routing\IRouter', new \Cubex\Routing\Router());
    $kernel->setCubex($cubex);
    $response = $kernel->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      true
    );

    if($response instanceof \Cubex\Responses\ApiResponse)
    {
      $apiObject = json_decode($response->getJson());
      $this->assertObjectHasAttribute('status', $apiObject);
      $this->assertObjectHasAttribute('result', $apiObject);
      $this->assertEquals($errMsg, $apiObject->status->message);
      $this->assertEquals($errNo, $apiObject->status->code);
      $this->assertEquals($expect, $apiObject->result);
      $this->assertEquals($errMsg, $response->getStatusMessage());
      $this->assertEquals($errNo, $response->getStatusCode());

      $this->expectOutputString($response->getJson());
      $response->send();
    }
  }

  public function handleProvider()
  {
    return [
      [
        '/testSuccess',
        (object)["username" => 'brooke', 'name' => 'Brooke Bryan'],
        '',
        200
      ],
      ['/testError', '', 'File not found', 404],
      ['/testErrorCodeless', '', 'Missing code', 400],
      ['/testNonCubexResponse', 'Strange Content', '', 200],
      [
        '/testSubKernel',
        (object)["username" => 'john', 'name' => 'John Smith'],
        '',
        200
      ],
    ];
  }

  public function testSubRoutes()
  {
    $apiTestKernel = new ApiTestKernel();
    $this->assertEquals(
      [
        '%s',
        '%sController',
        '%s\%sController',
        'ApiTestKernel\%s'
      ],
      $apiTestKernel->subRouteTo()
    );
  }

  /**
   * @param $path
   * @param $errType
   * @param $errMsg
   * @param $errNo
   *
   * @dataProvider uncaughtProvider
   */
  public function testUncaught($path, $errType, $errMsg, $errNo)
  {
    $request = \Cubex\Http\Request::create($path);
    $kernel  = new ApiTestKernel();
    $cubex   = $this->newCubexInstace();
    $cubex->instance('\Cubex\Routing\IRouter', new \Cubex\Routing\Router());
    $kernel->setCubex($cubex);
    $this->setExpectedException($errType, $errMsg, $errNo);
    $kernel->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
  }

  public function uncaughtProvider()
  {
    return [
      ['/testError', '\Exception', 'File not found', 404],
      ['/testErrorCodeless', '\Exception', 'Missing code', 0],
    ];
  }
}

class ApiTestKernel extends \Cubex\Kernel\ApiKernel
{
  public function testSuccess()
  {
    return ["username" => 'brooke', 'name' => 'Brooke Bryan'];
  }

  public function testError()
  {
    throw new Exception('File not found', 404);
  }

  public function testErrorCodeless()
  {
    throw new Exception('Missing code');
  }

  public function testNonCubexResponse()
  {
    return new \Symfony\Component\HttpFoundation\Response('Strange Content');
  }

  public function testSubKernel()
  {
    return new SubApiTestKernel();
  }
}

class SubApiTestKernel extends \Cubex\Kernel\ApiKernel
{
  public function defaultAction()
  {
    return ["username" => 'john', 'name' => 'John Smith'];
  }
}
