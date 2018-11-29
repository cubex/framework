<?php
namespace Project;

use Cubex\Controller\Controller;

class WorldHandler extends Controller
{
  public function __invoke()
  {
    return 'Hello World';
  }
}
