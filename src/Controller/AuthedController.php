<?php
namespace Cubex\Controller;

use Cubex\Context\Context;
use Symfony\Component\HttpFoundation\Response;

abstract class AuthedController extends Controller
{
  const ERROR_ACCESS_DENIED = "you are not permitted to access this url";

  /**
   * Is this request permitted to process
   *
   * Setting the response to a strict response, e.g. RedirectResponse, will process before the route
   *
   * @param $response
   *
   * @return bool
   */
  public function canProcess(&$response): bool
  {
    return true;
  }

  /**
   * Standard route handler, pre-authenticated
   *
   * @param Context $c
   *
   * @return Response
   * @throws \Throwable
   */
  public function handle(Context $c): Response
  {
    $response = null;
    $this->setContext($c);
    $this->_callStartTime = $this->_callStartTime ?: microtime(true);

    //Verify the request can be processed
    if($this->canProcess($response) !== true)
    {
      if($response instanceof Response)
      {
        return $this->_prepareResponse($c, $response, null);
      }
      throw new \Exception(self::ERROR_ACCESS_DENIED, 403);
    }

    return parent::handle($c);
  }
}
