<?php
namespace Cubex\Routing;

use Cubex\Http\FuncHandler;
use Cubex\Http\Handler;

class Router extends ConditionSelector
{
  public static function i()
  {
    return new static();
  }

  /**
   * @var ConditionHandler[]
   */
  protected $_conditions = [];
  protected $_defaultHandler;

  protected function _getConditions()
  {
    foreach($this->_conditions as $condition)
    {
      yield $condition;
    }
    return $this->_defaultHandler;
  }

  public function handleCondition(ConditionHandler $condition): Router
  {
    $this->_conditions[] = $condition;
    return $this;
  }

  public function setDefaultHandler(Handler $handler)
  {
    $this->_defaultHandler = $handler;
    return $this;
  }

  public function handle($path, Handler $handler): Condition
  {
    $condition = RequestConstraint::i()->path($path);
    $route = Route::with($condition);
    $this->handleCondition($route->setHandler($handler));
    return $condition;
  }

  public function handleFunc($path, callable $handleFunc): Condition
  {
    return $this->handle($path, new FuncHandler($handleFunc));
  }
}
