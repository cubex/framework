<?php
namespace testProject;

use Cubex\Http\Response;
use Cubex\Kernel\CubexKernel;

class Project extends CubexKernel
{
  public function getRoutes()
  {
    return [
      'hello/world' => Application::class,
      'meth'        => 'method',
      'google'      => 'http://www.google.com', //Redirect to url
      'go'          => '#@hello/world', //Redirect to hello/world
      'hi'          => ['world' => 'success']
    ];
  }

  public function renderMethod()
  {
    echo "Processing :)";
    //Comment out for a valid response containing the echo content
    return new Response("Hey There Method");
  }

  public function success()
  {
    echo "Processed sub route";
  }
}
