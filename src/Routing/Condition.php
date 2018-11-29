<?php
namespace Cubex\Routing;

use Cubex\Http\Handler;
use Cubex\Http\Request;

class Condition implements RoutingCondition
{
  private $_handler;
  protected $_matchAnything = false;
  protected $_constraints = [];

  public static function with(Constraint $constraint)
  {
    $cond = new static();
    $cond->_constraints = [$constraint];
    return $cond;
  }

  public function add(Constraint $constraint)
  {
    $this->_constraints[] = $constraint;
    return $this;
  }

  /**
   * @return Handler
   */
  public function getHandler(): Handler
  {
    return $this->_handler;
  }

  /**
   * @param Handler $handler
   *
   * @return Condition
   */
  public function setHandler(Handler $handler)
  {
    $this->_handler = $handler;
    return $this;
  }

  public function match(Request $request): bool
  {
    foreach($this->_constraints as $constraint)
    {
      if(!$constraint->match($request))
      {
        return false;
      }
    }
    return true;
  }

}
