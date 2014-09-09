<?php
namespace Cubex\Testing;

use Cubex\Cubex;
use Cubex\Http\Request;
use Cubex\ICubexAware;
use Cubex\View\Layout;
use Symfony\Component\HttpFoundation\RedirectResponse;

abstract class CubexTestCase extends \PHPUnit_Framework_TestCase
  implements ICubexAware
{
  protected $_cubex;
  protected static $_globalCubex;
  private $_lastResponse;

  /**
   * Set the cubex application
   *
   * @param Cubex $app
   *
   */
  public function setCubex(Cubex $app)
  {
    $this->_cubex = $app;
  }

  /**
   * Retrieve the cubex application
   *
   * @return Cubex
   */
  public function getCubex()
  {
    if($this->_cubex === null)
    {
      $this->_cubex = self::$_globalCubex;
    }
    return $this->_cubex;
  }

  public function setLastResponse(TestResponse $response)
  {
    $this->_lastResponse = $response;
    return $this;
  }

  /**
   * Retrieve a response from the project
   *
   * @param        $uri
   * @param string $method
   * @param array  $parameters
   *
   * @return TestResponse
   * @throws \Exception
   */
  public function getResponse($uri, $method = 'GET', $parameters = [])
  {
    $request = Request::create($uri, $method, $parameters);
    $this->setLastResponse(
      new TestResponse(
        $this->getCubex()->handle($request)
      )
    );
    return $this->getLastResponse();
  }

  /**
   * Retrieve the last processed response
   *
   * @return TestResponse
   */
  public function getLastResponse()
  {
    return $this->_lastResponse;
  }

  /**
   * Assert a response was instructed to redirect to a specific uri
   *
   * @param              $uri
   * @param TestResponse $response
   */
  public function assertRedirectedTo($uri, TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }

    $raw = $response->getResponse();
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\RedirectResponse',
      $raw
    );
    /**
     * @var $raw RedirectResponse
     */
    $this->assertEquals($uri, $raw->getTargetUrl());
  }

  /**
   * Assert the response was a 200 status code
   *
   * @param TestResponse $response
   */
  public function assertResponseOk(TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertEquals(200, $response->getResponse()->getStatusCode());
  }

  /**
   * Assert a specific status code
   *
   * @param int          $expect
   * @param TestResponse $response
   */
  public function assertStatusCode($expect = 200, TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertEquals($expect, $response->getResponse()->getStatusCode());
  }

  /**
   * Assert the response contains a string
   *
   * @param              $expect
   * @param TestResponse $response
   */
  public function assertResponseContains($expect, TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertContains($expect, $response->getContent());
  }

  /**
   * Assert the response is a cubex formed response
   *
   * @param TestResponse $response
   */
  public function assertReturnsCubexResponse(TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertTrue($response->hasOriginal());
  }

  /**
   * Assert a view model was returned from the request
   *
   * @param TestResponse $response
   */
  public function assertReturnsViewModel(TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertReturnsInstanceOf('\Cubex\View\ViewModel', $response);
  }

  /**
   * Assert a layout was returned from the request
   *
   * @param TestResponse $response
   */
  public function assertReturnsLayout(TestResponse $response = null)
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertReturnsInstanceOf('\Cubex\View\Layout', $response);
  }

  /**
   * Retrieve layout section
   *
   * @param string       $name
   * @param TestResponse $response
   *
   * @return \Illuminate\Support\Contracts\RenderableInterface
   * @throws \Exception
   */
  public function getLayoutSection(
    $name = 'content', TestResponse $response = null
  )
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertReturnsCubexResponse($response);
    $this->assertReturnsLayout($response);
    $layout = $response->getOriginal();
    /**
     * @var $layout Layout
     */
    return $layout->get($name);
  }

  /**
   * Assert a specific type was returned from the request
   *
   * @param              $expect
   * @param TestResponse $response
   */
  public function assertReturnsInstanceOf(
    $expect, TestResponse $response = null
  )
  {
    if($response == null)
    {
      $response = $this->_lastResponse;
    }
    $this->assertReturnsCubexResponse($response);
    $this->assertInstanceOf($expect, $response->getOriginal());
  }
}
