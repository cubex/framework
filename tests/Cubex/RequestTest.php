<?php

class RequestTest extends PHPUnit_Framework_TestCase
{
  public function testExtendsSymfonyRequest()
  {
    $request = new \Cubex\Http\Request();
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Request',
      $request
    );
  }

  public function testPort()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $request->headers->set('HOST', 'localhost:8080');
    $this->assertEquals(8080, $request->port());
  }

  /**
   * @dataProvider httpHostsProvider
   */
  public function testDomainParts(
    $subDomain = null, $domain = null, $tld = null
  )
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $host    = trim(implode('.', func_get_args()), '.');
    $request->headers->set('HOST', $host);

    $this->assertEquals($subDomain, $request->subDomain());
    $this->assertEquals($domain, $request->domain());
    $this->assertEquals($tld, $request->tld());
    $this->assertEquals($tld, $request->tld());
  }

  public function httpHostsProvider()
  {
    return array(
      array(),
      array(null, 'localhost'),
      array("www", "cubex", "local"),
      array("www", "cubex", "local"),
      array("www", "cubex", "co.uk"),
      array("beta.www", "cubex", "io"),
      array("beta.www", "cubex", "co.uk"),
    );
  }

  public function testUrlSprintf()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $request->headers->set('HOST', 'www.cubex.local:81');
    $request->server->set('REQUEST_URI', '/path');

    $this->assertEquals("81", $request->urlSprintf("%r"));
    $this->assertEquals("/path", $request->urlSprintf("%i"));
    $this->assertEquals("http://", $request->urlSprintf("%p"));
    $this->assertEquals("www.cubex.local:81", $request->urlSprintf("%h"));
    $this->assertEquals("cubex", $request->urlSprintf("%d"));
    $this->assertEquals("www", $request->urlSprintf("%s"));
    $this->assertEquals("local", $request->urlSprintf("%t"));
    $this->assertEquals(
      "http://www.cubex.local",
      $request->urlSprintf("%p%s.%d.%t")
    );
  }

  public function testMatchDomain()
  {
    $request = \Cubex\Http\Request::createFromGlobals();

    $request->headers->set('HOST', 'www.cubex.local');
    $this->assertTrue($request->matchDomain("cubex"));
    $this->assertTrue($request->matchDomain("cubex", "local"));
    $this->assertTrue($request->matchDomain("cubex", "local", "www"));
  }

  public function testDefinedTlds()
  {
    $request = \Cubex\Http\Request::createFromGlobals();
    $this->assertEmpty($request->getDefinedTlds());
    $request->defineTlds(['replace']);
    $request->defineTlds(['dev', 'cubex'], false);
    $this->assertEquals(['dev', 'cubex'], $request->getDefinedTlds());
    $request->defineTlds(['devx'], true);
    $this->assertEquals(['dev', 'cubex', 'devx'], $request->getDefinedTlds());
  }
}
