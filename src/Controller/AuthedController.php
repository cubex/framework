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
   * @return bool|Response
   */
  public function canProcess()
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
    $this->setContext($c);
    $this->_callStartTime = $this->_callStartTime ?: microtime(true);

    //Verify the request can be processed
    $authResponse = $this->canProcess();
    if($authResponse instanceof Response)
    {
      return $authResponse;
    }
    else if($authResponse !== true)
    {
      throw new \Exception(self::ERROR_ACCESS_DENIED, 403);
    }

    return parent::handle($c);
  }
}
