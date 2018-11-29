<?php
namespace Cubex\Kernel;

use Cubex\Context\Context;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Http\Handler;
use Cubex\Http\Request;
use Cubex\Http\Response;

class Controller implements Handler, ContextAware
{
  use ContextAwareTrait;

  public function getRoutes()
  {
    return [];
  }

  public function handle(Context $c, Response $w, Request $r)
  {
    $this->setContext($c);

    'hello/bob';
    '/hello/bob';


    //Loop over routes
    //Extract route data into context
    //Process method|class(Handler)
    //{httpMethod}MethodName
    //throw FuckedException();
  }

  public function getMethod() {
    $data = $this->ajaxGetMethod();
    return new Render($data);
  }

  public function postMethod()
  {
    return $this->getMethod();
  }

  public function ajaxPostMethod()
  {

  }

  public function ajaxGetMethod()
  {
    return ['data' => 'abc'];
  }
}
