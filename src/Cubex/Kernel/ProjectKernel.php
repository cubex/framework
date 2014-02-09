<?php
namespace Cubex\Kernel;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Your main project should extend this class
 */
abstract class ProjectKernel extends CubexKernel
{
  /**
   * Handles a Request to convert it to a Response.
   *
   * When $catch is true, the implementation must catch all exceptions
   * and do its best to convert them to a Response instance.
   *
   * @param Request $request A Request instance
   * @param integer $type The type of the request
   *                          (one of HttpKernelInterface::MASTER_REQUEST or HttpKernelInterface::SUB_REQUEST)
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
    $this->getCubex()->instance("project", $this);

    // TODO: Route to correct application based on request parts of project logic

    //Imitialise the project

    //Get the application
    //Set cubex on the application

    //shutdown the project

    $response = new \Cubex\Http\Response("Hello Cubex");
    return $response;
  }
}
