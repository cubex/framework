<?php
namespace Cubex\Routing;

interface IRoutable
{
  /**
   * Get available routes for this object
   *
   * @return array|null
   */
  public function getRoutes();
}
