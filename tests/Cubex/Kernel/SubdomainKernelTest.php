<?php

namespace CubexTest\Cubex\Kernel;

use Cubex\Cubex;
use Cubex\Http\Request as CubexRequest;
use Cubex\Http\Response;
use Cubex\Kernel\SubdomainKernel;
use CubexTest\InternalCubexTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SubdomainKernelTestInternal extends InternalCubexTestCase
{
  public function getKernel($defaultAction = 'abc')
  {
    $cubex = new Cubex();
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
      Request::createFromGlobals(),
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
  }

  /**
   * @dataProvider methodCallsProvider
   *
   * @param $subdomain
   * @param $expect
   * @param $catch
   *
   * @throws \Exception
   */
  public function testMethodCalls($subdomain, $expect, $catch = true)
  {
    $request = CubexRequest::createConsoleRequest();
    $request->headers->set('HOST', $subdomain . '.domain.tld');
    $class = $this->getKernel();

    if(!$catch)
    {
      $this->setExpectedException('Exception', $expect);
    }

    $response = $class->handle(
      $request,
      HttpKernelInterface::MASTER_REQUEST,
      $catch
    );

    if(is_scalar($expect))
    {
      $this->assertContains($expect, $response->getContent());
    }
    else
    {
      if($response instanceof Response)
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
      CubexRequest::createFromGlobals(),
      HttpKernelInterface::MASTER_REQUEST,
      false
    );
    $this->assertContains('Please Login', (string)$result);
  }
}

class SubDomainTester extends SubdomainKernel
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

class SubDomainKernelAuthTest extends SubdomainKernel
{
  public function canProcess()
  {
    return new Response('Please Login', 200);
  }
}

