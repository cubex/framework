<?php
namespace Cubex\Controller;

/**
 * Class SingleRouteController
 *
 * GET > getReq
 * POST > postReq
 * AJAX > ajaxReq
 * Any Method > processReq
 *
 */
class SingleRouteController extends Controller
{
  public function getRoutes()
  {
    return 'req';
  }
}
