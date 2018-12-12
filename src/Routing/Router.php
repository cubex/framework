<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Cubex\Http\FuncHandler;
use Cubex\Http\Handler;

class Router
{
  public static function i()
  {
    return new static();
  }

  /**
   * @var ConditionHandler[]
   */
  protected $_conditions = [];

  public function handleCondition(ConditionHandler $condition): Router
  {
    $this->_conditions[] = $condition;
    return $this;
  }

  public function handle($path, Handler $handler): Constraint
  {
    $condition = Constraint::path($path);
    $route = Route::with($condition);
    $this->handleCondition($route->setHandler($handler));
    return $condition;
  }

  public function handleFunc($path, callable $handleFunc): Constraint
  {
    return $this->handle($path, new FuncHandler($handleFunc));
  }

  public function getHandler(Context $context)
  {
    foreach($this->_conditions as $condition)
    {
      if($condition->match($context))
      {
        return $condition->getHandler();
      }
    }
    return null;
  }
}
