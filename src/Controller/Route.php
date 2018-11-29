<?php
namespace Cubex\Controller;

use Cubex\Http\Request;
use Cubex\Routing\Constraint;
use Cubex\Routing\RequestConstraint;

class Route implements Constraint
{
  /**
   * @var RequestConstraint
   */
  protected $_constraint;
  protected $_result;

  public static function i($path, $result)
  {
    $route = new static();
    $route->_constraint = RequestConstraint::path($path);
    $route->_result = $result;
    return $route;
  }

  public function match(Request $request): bool
  {
    return $this->_constraint->match($request);
  }

  /**
   * @return string methodName | class::class
   */
  public function getResult()
  {
    return $this->_result;
  }
}
