<?php
namespace CubexTest\Cubex\Http;

use Cubex\Http\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{
  public function testExtendsSymfonyRequest()
  {
    $request = new Request();
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Request',
      $request
    );
  }

  public function testPort()
  {
    $request = Request::createFromGlobals();
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
    $request = Request::createFromGlobals();
    $host = trim(implode('.', func_get_args()), '.');
    $request->headers->set('HOST', $host);

    $this->assertEquals($subDomain, $request->subDomain());
    $this->assertEquals($domain, $request->domain());
    $this->assertEquals($tld, $request->tld());
    $this->assertEquals($tld, $request->tld());
  }

  public function httpHostsProvider()
  {
    return [
      [],
      [null, 'localhost'],
      ["www", "cubex", "local"],
      ["www", "cubex", "local"],
      ["www", "cubex", "co.uk"],
      ["beta.www", "cubex", "io"],
      ["beta.www", "cubex", "co.uk"],
    ];
  }

  public function testUrlSprintf()
  {
    $request = Request::createFromGlobals();
    $request->headers->set('HOST', 'www.cubex.local:81');
    $request->server->set('REQUEST_URI', '/path');

    $this->assertEquals("81", $request->urlSprintf("%r"));
    $this->assertEquals(":81", $request->urlSprintf("%o"));
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

  public function testStandardPort()
  {
    $request = Request::createFromGlobals();
    $request->headers->set('HOST', 'www.cubex.local:81');
    $request->server->set('REQUEST_URI', '/path');
    $this->assertFalse($request->isStandardPort());

    $request->headers->set('HOST', 'www.cubex.local:80');
    $request->server->set('REQUEST_URI', '/path');
    $this->assertTrue($request->isStandardPort());
  }

  public function testMatchDomain()
  {
    $request = Request::createFromGlobals();

    $request->headers->set('HOST', 'www.cubex.local');
    $this->assertTrue($request->matchDomain("cubex", null, null));
    $this->assertTrue($request->matchDomain("cubex", "local", null));
    $this->assertTrue($request->matchDomain("cubex", "local", "www"));
    $this->assertFalse($request->matchDomain("packaged", null, null));
    $this->assertFalse($request->matchDomain("cubex", "dev", null));
    $this->assertFalse($request->matchDomain("cubex", "local", "wibble"));
  }

  public function testDefinedTlds()
  {
    $request = Request::createFromGlobals();
    $this->assertEmpty($request->getDefinedTlds());
    $request->defineTlds(['replace']);
    $request->defineTlds(['dev', 'cubex'], false);
    $this->assertEquals(['dev', 'cubex'], $request->getDefinedTlds());
    $request->defineTlds(['devx'], true);
    $this->assertEquals(['dev', 'cubex', 'devx'], $request->getDefinedTlds());
  }

  /**
   * @dataProvider pathProvider
   *
   * @param     $path
   * @param     $expect
   * @param     $offset
   * @param int $depth
   */
  public function testPaths($path, $expect, $depth = 1, $offset = -1)
  {
    $request = Request::createFromGlobals();
    /**
     * @var \Cubex\Http\Request $request
     */
    $request->server->set('REQUEST_URI', $path);
    if($offset == -1)
    {
      $this->assertEquals($expect, $request->path($depth));
    }
    else
    {
      $this->assertEquals($expect, $request->offsetPath($offset, $depth));
    }
  }

  public function pathProvider()
  {
    return [
      ['/hello/world/hi', '/hello', 1],
      ['/hello/world/hi', '/hello/world', 2],
      ['/hello/world/hi', '/hello/world/hi', 3],
    ];
  }

  /**
   * @dataProvider offsetPathProvider
   *
   * @param     $path
   * @param     $expect
   * @param int $offset
   * @param int $limit
   */
  public function testOffsetPaths($path, $expect, $offset = 0, $limit = null)
  {
    $request = Request::createFromGlobals();
    /**
     * @var \Cubex\Http\Request $request
     */
    $request->server->set('REQUEST_URI', $path);
    $this->assertEquals($expect, $request->offsetPath($offset, $limit));
  }

  public function offsetPathProvider()
  {
    $path = '/hello/world/how/are/you';
    return [
      [$path, '/hello', 0, 1],
      [$path, '/world', 1, 1],
      [$path, '/you', -1],
      [$path, '/are/you', -2],
      [$path, '/hello/world/how', 0, -2],
      [$path, '/world/how', 1, -2],
    ];
  }

  public function testCreateConsoleRequest()
  {
    $_SERVER['consoletest'] = 'tested';
    $request = Request::createConsoleRequest();
    $this->assertEquals('localhost', $request->getHost());
    $this->assertEquals('GET', $request->getMethod());
    $this->assertEquals('http', $request->getScheme());
    $this->assertEquals('tested', $request->server->get('consoletest'));
    unset($_SERVER['consoletest']);
  }

  /**
   * @dataProvider privateNetworkProvider
   *
   * @param $remoteAddr
   * @param $isPrivate
   */
  public function testIsPrivateNetwork($remoteAddr, $isPrivate)
  {
    $request = new Request();
    $server = ['REMOTE_ADDR' => $remoteAddr];
    $request->initialize([], [], [], [], [], $server);
    $this->assertEquals($isPrivate, $request->isPrivateNetwork());
    $this->assertEquals($isPrivate, $request->isPrivateNetwork($remoteAddr));
  }

  public function privateNetworkProvider()
  {
    return [
      ['10.0.1.1', true],
      ['192.168.0.1', true],
      ['172.16.0.2', true],
      ['127.0.0.1', true],
      ['123.123.123.123', false]
    ];
  }

  public function testUserAgent()
  {
    $request = new Request();
    $this->assertNull($request->userAgent());

    $request = new Request();
    $this->assertEquals('MY_UA', $request->userAgent('MY_UA'));

    $request = new Request();
    $server = ['HTTP_USER_AGENT' => 'Cubex Browser'];
    $request->initialize([], [], [], [], [], $server);
    $this->assertEquals('Cubex Browser', $request->userAgent());
  }

  public function testReferrer()
  {
    $request = new Request();
    $this->assertNull($request->referrer());

    $request = new Request();
    $this->assertEquals('MY_REF', $request->userAgent('MY_REF'));

    $request = new Request();
    $server = ['HTTP_REFERER' => 'http://www.cubex.io'];
    $request->initialize([], [], [], [], [], $server);
    $this->assertEquals('http://www.cubex.io', $request->referrer());
  }

  public function testXForwardedFor()
  {
    //http://en.wikipedia.org/wiki/X-Forwarded-For#Format
    $request = new Request();
    //8.8.8.8 = client IP
    //4.4.4.4 = proxy1
    $server = [
      'HTTP_X_FORWARDED_FOR' => '8.8.8.8, 4.4.4.4',
      'REMOTE_ADDR'          => '8.8.4.4',
    ];
    $request->initialize([], [], [], [], [], $server);
    $request->setTrustedProxies(['8.8.4.4']);
    $this->assertEquals('8.8.8.8', $request->getClientIp());
  }
}
