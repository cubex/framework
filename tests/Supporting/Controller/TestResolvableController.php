<?php
namespace Cubex\Tests\Supporting\Controller;

use Cubex\Context\Context;
use Cubex\Controller\Controller;
use Cubex\Tests\Supporting\TestObject;

class TestResolvableController extends Controller
{
  protected Context $_context;

  public function __construct(Context $ctx)
  {
    $this->_context = $ctx;
  }

  protected function _generateRoutes()
  {
    return 'default';
  }

  public function getDefault(TestObject $testObject)
  {
    return 'Default ' . $testObject->name;
  }

  public function getCustomContext(): \Packaged\Context\Context
  {
    return $this->_context;
  }
}
