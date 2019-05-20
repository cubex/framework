<?php
namespace Cubex\Application;

use Cubex\Cubex;
use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\Routing\RouteProcessor;
use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Packaged\Routing\Handler\Handler;

/**
 * Base Application
 */
abstract class Application extends RouteProcessor implements ContextAware, CubexAware
{
  use CubexAwareTrait;
  use ContextAwareTrait;

  public function __construct(Cubex $cubex)
  {
    $this->setCubex($cubex);
  }

  protected function _generateRoutes()
  {
    $this->_initialize();
    return $this->_defaultHandler();
  }

  protected function _initialize()
  {
    //This is executed before the default handler route is yielded
  }

  /**
   * Your default handler for the application
   *
   * @return Handler
   */
  abstract protected function _defaultHandler(): Handler;
}
