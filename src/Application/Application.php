<?php
namespace Cubex\Application;

use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\Http\Handler;
use Cubex\Routing\RouteProcessor;

/**
 * Base Application
 */
abstract class Application extends RouteProcessor implements ContextAware
{
  use CubexAwareTrait;
  use ContextAwareTrait;

  public function __construct(Cubex $cubex)
  {
    $this->setCubex($cubex);
  }

  protected function _generateRoutes()
  {
    return $this->_defaultHandler();
  }

  /**
   * Your default handler for the application
   *
   * @return Handler
   */
  abstract protected function _defaultHandler(): Handler;
}
