<?php
namespace Cubex\Routing;

use Cubex\Http\FuncHandler;
use Cubex\Http\Handler;
use Cubex\Http\Request;

class Router
{
  /**
   * @var RoutingCondition[]
   */
  protected $_conditions = [];

  public function handleCondition(RoutingCondition $condition): RoutingCondition
  {
    $this->_conditions[] = $condition;
    return $condition;
  }

  public function handle($path, Handler $handler): RoutingCondition
  {
    $condition = Condition::with(RequestConstraint::path($path));
    return $this->handleCondition($condition->setHandler($handler));
  }

  public function handleFunc($path, callable $handleFunc)
  {
    return $this->handle($path, new FuncHandler($handleFunc));
  }

  public function getHandler(Request $request): ?Handler
  {
    foreach($this->_conditions as $condition)
    {
      if($condition->match($request))
      {
        return $condition->getHandler();
      }
    }
    return null;
  }
}
