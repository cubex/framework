<?php
namespace CubexTest\Cubex\Testing;

use Cubex\Http\Response;
use Cubex\Testing\CubexTestCase;
use Cubex\Testing\TestResponse;
use Cubex\View\Layout;
use Cubex\View\Renderable;
use namespaced\sub\TestView;
use namespaced\TestLayoutController;
use namespaced\TestViewModel;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CubexTestCaseTest extends \PHPUnit_Framework_TestCase
{
  public function testResponseSetAndGet()
  {
    $response = new TestResponse(new Response('Test'));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $this->assertSame($response, $test->getLastResponse());
  }

  public function testGetRequest()
  {
    $test     = new MockCubexTestCase();
    $response = $test->getResponse('/');
    $this->assertSame($response, $test->getLastResponse());
  }

  public function testAssertRedirectedTo()
  {
    $response = new TestResponse(new RedirectResponse('/test'));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertRedirectedTo('/test', $response);
    $test->assertRedirectedTo('/test');
  }

  public function testAssertResponseOk()
  {
    $response = new TestResponse(new Response('Test'));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertResponseOk($response);
    $test->assertResponseOk();
  }

  public function testAssertStatusCode()
  {
    $response = new TestResponse(new Response('Test', 123));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertStatusCode(123, $response);
    $test->assertStatusCode(123);
  }

  public function testAssertResponseContains()
  {
    $response = new TestResponse(new Response('Test'));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertResponseContains('est', $response);
    $test->assertResponseContains('est');
  }

  public function testAssertReturnsCubexResponse()
  {
    $response = new TestResponse(new Response('Test'));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertReturnsCubexResponse($response);
    $test->assertReturnsCubexResponse();
  }

  public function testAssertReturnsViewModel()
  {
    $response = new TestResponse(Response::create(new TestViewModel()));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertReturnsViewModel($response);
    $test->assertReturnsViewModel();
  }

  public function testAssertReturnsInstanceOf()
  {
    $response = new TestResponse(Response::create(new Renderable('Testing')));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertReturnsInstanceOf('\Cubex\View\Renderable', $response);
    $test->assertReturnsInstanceOf('\Cubex\View\Renderable');
  }

  public function testAssertReturnsLayout()
  {
    $response = new TestResponse(
      Response::create(new Layout(new TestLayoutController()))
    );
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $test->assertReturnsLayout($response);
    $test->assertReturnsLayout();
  }

  public function testGetLayoutSection()
  {
    $layout = new Layout(new TestLayoutController());
    $view   = new TestView();
    $layout->insert('content', $view);
    $response = new TestResponse(Response::create($layout));
    $test     = new MockCubexTestCase();
    $test->setLastResponse($response);
    $this->assertSame($view, $test->getLayoutSection());
  }
}

class MockCubexTestCase extends CubexTestCase
{
}
