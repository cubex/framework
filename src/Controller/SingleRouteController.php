<?php
namespace Cubex\Controller;

/**
 * Class SingleRouteController
 *
 * GET > getRoute
 * POST > postRoute
 * AJAX > ajaxRoute
 * Any Method > processRoute
 *
 */
class SingleRouteController extends Controller
{
  protected function _generateRoutes()
  {
    return 'route';
  }
}
