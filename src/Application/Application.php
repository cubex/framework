<?php
namespace Cubex\Application;

use Cubex\Cubex;
use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\Routing\RouteProcessor;
use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Packaged\Http\Responses\TextResponse;
use Packaged\Routing\Handler\FuncHandler;
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
    return $this->_defaultHandler();
  }

  /**
   * Your default handler for the application
   *
   * @return Handler
   */
  protected function _defaultHandler(): Handler
  {
    return new FuncHandler(
      function () { return TextResponse::create('Not Found', 404); }
    );
  }
}
