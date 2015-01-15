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

  /**
   * Get the parameters available within the route
   *
   * @return array|null
   */
  public function getRouteData();

  /**
   * Get the parameters available within the route
   *
   * @return array|null
   */
  public function getMatchedPath();
}
