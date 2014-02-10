<?php
namespace testProject;

use Cubex\Http\Response;
use Cubex\Kernel\CubexKernel;

class Application extends CubexKernel
{
  public function defaultAction()
  {
    return new Response("Default Response");
  }
}
