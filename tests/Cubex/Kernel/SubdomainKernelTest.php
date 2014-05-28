<?php

class SubdomainKernelTest extends CubexTestCase
{
  /**
   * @return \Cubex\Kernel\CubexKernel
   */
  public function getKernel($defaultAction = 'abc')
  {
    $cubex = new \Cubex\Cubex();
    $cubex->prepareCubex();
    $cubex->processConfiguration($cubex->getConfiguration());
    $kernel = new SubDomainTester();
    $kernel->setDefaultResponse($defaultAction);
    $kernel->setCubex($cubex);
    return $kernel;
  }

  public function testThrowsExceptionWithBadRequest()
  {
    $this->setExpectedException('RuntimeException');
    $kernel = $this->getKernel();
    $kernel->handle(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
  }

  /**
   * @dataProvider methodCallsProvider
   *
   * @param $subdomain
   * @param $expect
   *
   * @throws Exception
   */
  public function testMethodCalls($subdomain, $expect, $catch = true)
  {
    $request = \Cubex\Http\Request::createConsoleRequest();
    $request->headers->set('HOST', $subdomain . '.domain.tld');
    $class = $this->getKernel();

    if(!$catch)
    {
      $this->setExpectedException('Exception', $expect);
    }

    $response = $class->handle(
      $request,
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      $catch
    );

    if(is_scalar($expect))
    {
      $this->assertContains($expect, $response->getContent());
    }
    else
    {
      if($response instanceof \Cubex\Http\Response)
      {
        $actual = $response->getOriginalResponse();
      }
      else
      {
        $actual = $response->getContent();
      }

      $this->assertEquals($expect, $actual);
    }
  }

  public function methodCallsProvider()
  {
    return [
      ['www', 'true'],
      ['subdomain', 'false'],
      ['missing', 'abc'],
      ['fail', 'The page you requested was not found'],
      ['exceptions', 'Something Failed'],
      ['exceptions', 'Something Failed', false],
    ];
  }

  public function testCanProcess()
  {
    $kernel = new SubDomainKernelAuthTest();
    $result = $kernel->handle(
      \Cubex\Http\Request::createFromGlobals(),
      \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('Please Login', (string)$result);
  }
}

class SubDomainTester extends \Cubex\Kernel\SubdomainKernel
{
  protected $_default;

  public function setDefaultResponse($response)
  {
    $this->_default = $response;
  }

  public function defaultAction()
  {
    return $this->_default;
  }

  public function fail()
  {
    return null;
  }

  public function exceptions()
  {
    throw new \Exception("Something Failed");
  }

  public function www()
  {
    return 'true';
  }

  public function subdomain()
  {
    return 'false';
  }
}

class SubDomainKernelAuthTest extends \Cubex\Kernel\SubdomainKernel
{
  public function canProcess()
  {
    return new \Cubex\Http\Response('Please Login', 200);
  }
}

