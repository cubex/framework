<?php
namespace Cubex\Kernel;

use Cubex\CubexException;
use Symfony\Component\HttpFoundation\Request;
use \Cubex\Http\Request as CubexRequest;
use Symfony\Component\HttpFoundation\Response;

abstract class SubdomainProjectKernel extends CubexKernel
{
  /**
   * Handles a Request to convert it to a Response.
   *
   * When $catch is true, the implementation must catch all exceptions
   * and do its best to convert them to a Response instance.
   *
   * @param Request $request A Request instance
   * @param integer $type The type of the request
   *                          (one of HttpKernelInterface::MASTER_REQUEST
   *                          or HttpKernelInterface::SUB_REQUEST)
   * @param Boolean $catch Whether to catch exceptions or not
   *
   * @return Response A Response instance
   *
   * @throws \Exception When an Exception occurs during processing
   *
   * @api
   */
  public function handle(
    Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    if(!($request instanceof CubexRequest))
    {
      throw new \RuntimeException("Invalid cubex request");
    }

    //Imitialise the kernel
    $this->init();

    try
    {
      //Check to see if the request is allowed to process
      $authed = $this->canProcess();

      //If can process returns a response, use that to send back to the user
      if($authed instanceof Response)
      {
        return $authed;
      }

      $response = $this->_processResponse(
        $this->_getMethodResult($request->subDomain()),
        $request,
        $type,
        $catch
      );

      if(!($response instanceof Response))
      {
        throw CubexException::debugException(
          "The subdomain requested is not be supported",
          500,
          $response
        );
      }
    }
    catch(CubexException $e)
    {
      $this->shutdown();
      return $this->getCubex()->make('404');
    }
    catch(\Exception $e)
    {
      if($catch)
      {
        //shutdown the kernel
        $this->shutdown();
        return $this->getCubex()->exceptionResponse($e);
      }
      else
      {
        //shutdown the kernel
        $this->shutdown();

        throw $e;
      }
    }

    //shutdown the kernel
    $this->shutdown();

    return $response;
  }
}
