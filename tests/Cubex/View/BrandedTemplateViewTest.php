<?php
namespace CubexTest\Cubex\View;

use Cubex\Cubex;
use Cubex\Http\Request;
use Cubex\View\BrandedTemplateView;

class BrandedTemplateViewTest extends \PHPUnit_Framework_TestCase
{
  public function prepareViewModel(Request $request = null)
  {
    $view = $this->getMockForAbstractClass('\Cubex\View\BrandedTemplateView');
    /**
     * @var $view BrandedTemplateView
     */
    $view->setTemplateDir(__DIR__ . DIRECTORY_SEPARATOR . 'res');
    $view->setTemplateFile('branded');
    if($request !== null)
    {
      $cubex = new Cubex();
      $cubex->instance('request', $request);
      $view->setCubex($cubex);
    }
    return $view;
  }

  /**
   * @param $host
   * @param $locale
   *
   * @return \Cubex\Http\Request
   */
  public function createRequest($host, $locale = 'en')
  {
    $request = Request::createFromGlobals();
    $request->server->set('SERVER_NAME', $host);
    $request->setLocale($locale);
    return $request;
  }

  public function testFullReplacement()
  {
    $request = $this->createRequest('www.replace.com');
    $view    = $this->prepareViewModel($request);
    $this->assertContains('Replaced', $view->render());
  }

  public function testBuildsRequestFromGlobals()
  {
    $view = $this->prepareViewModel();
    $this->assertContains('Default branded page', $view->render());
  }

  public function testPrePost()
  {
    $request = $this->createRequest('www.custom.com');
    $view    = $this->prepareViewModel($request);
    $this->assertContains('Pre', $view->render());
    $this->assertContains('Default branded page', $view->render());
    $this->assertContains('Post', $view->render());
  }

  public function testLanguage()
  {
    $request = $this->createRequest('www.custom.test', 'it');
    $view    = $this->prepareViewModel($request);
    $this->assertContains('Pre', $view->render());
    $this->assertContains('Italian Version', $view->render());
    $this->assertContains('Post', $view->render());
  }

  public function testException()
  {
    $this->setExpectedException('Exception', 'Broken Language');
    $request = $this->createRequest('www.custom.test', 'fr');
    $view    = $this->prepareViewModel($request);
    $view->render();
  }
}
