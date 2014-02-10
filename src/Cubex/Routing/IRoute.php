<?php
namespace Cubex\Routing;

interface IRoute
{
  /**
   * Get the value of the route
   *
   * @return mixed
   */
  public function getValue();
}
