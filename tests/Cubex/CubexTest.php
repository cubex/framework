<?php

class CubexTest extends PHPUnit_Framework_TestCase
{
  public function testConfiguration()
  {
    $config = new \Packaged\Config\Provider\Test\TestConfigProvider();
    $cubex  = new \Cubex\Cubex();
    $this->assertEquals(null, $cubex->getConfiguration());
    $this->assertEquals($cubex, $cubex->configure($config));
    $this->assertEquals($config, $cubex->getConfiguration());
  }

  public function testExceptions()
  {
    $cubex     = new \Cubex\Cubex();
    $exception = new Exception("Test Exception", 345);
    $resp      = $cubex->exceptionResponse($exception);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
    $this->assertContains('Uncaught Exception', (string)$resp);
    $this->assertContains('Test Exception', (string)$resp);
    $this->assertContains('345', (string)$resp);
  }

  public function testHandle()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $cubex   = new \Cubex\Cubex();
    $resp    = $cubex->handle($request);
    $this->assertTrue($cubex->bound('request'));
    $this->assertInstanceOf('\Cubex\Http\Request', $cubex->make('request'));
    $this->assertContains('Hello Cubex', (string)$resp);
  }

  public function testInvalidRequest()
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $cubex   = new \Cubex\Cubex();

    $resp = $cubex->handle($request);
    $this->assertContains('Uncaught Exception', (string)$resp);
    $this->assertContains('You must use a \Cubex\Http\Request', (string)$resp);

    $this->setExpectedException(
      '\InvalidArgumentException',
      'You must use a \Cubex\Http\Request'
    );
    $cubex->handle(
      $request,
      \Cubex\Cubex::MASTER_REQUEST,
      false
    );

    $resp = $cubex->handle($request);
    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
    $this->assertContains('Uncaught Exception', (string)$resp);
    $this->assertContains('You must use a \Cubex\Http\Request', (string)$resp);
  }

  public function testTerminate()
  {
    $cubex = new \Cubex\Cubex();
    $term  = $cubex->terminate(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
      new \Symfony\Component\HttpFoundation\Response('Hello World')
    );
    $this->assertNull($term);
  }
}
