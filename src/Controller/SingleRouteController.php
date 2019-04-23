<?php
namespace Cubex\Controller;

/**
 * Routes to method name matching HTTP method
 *
 * GET > get
 * POST > post
 * AJAX > ajax
 * Any Method > process
 */
class SingleRouteController extends Controller
{
  protected function _generateRoutes()
  {
    return '';
  }
}
