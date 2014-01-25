<?php
namespace Cubex;

use Illuminate\Container\Container;
use Packaged\Config\ConfigProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use \Cubex\Http\Request as CubexRequest;
use \Cubex\Http\Response as CubexResponse;

/**
 * Cubex Container, to be passed around for dependency injection etc
 */
class Cubex extends Container
  implements HttpKernelInterface, TerminableInterface
{
  protected $_configuration;

  /**
   * Configure Cubex
   *
   * @param ConfigProviderInterface $configuration
   *
   * @return $this
   */
  public function configure(ConfigProviderInterface $configuration)
  {
    $this->_configuration = $configuration;
    return $this;
  }

  /**
   * Retrieve the Cubex configuration
   *
   * @return ConfigProviderInterface|null
   */
  public function getConfiguration()
  {
    return $this->_configuration;
  }

  /**
   * Automatically build any missing elements, such as configurations
   */
  public function prepareCubex()
  {
    if($this->_configuration === null)
    {
      //TODO: Load default configuration
    }
  }

  public function exceptionResponse(\Exception $e)
  {
    $content = '<h1>Uncaught Exception</h1>';
    $content .= '<h2>(' . $e->getCode() . ') ' . $e->getMessage() . '</h2>';
    $content .= '<pre>' . $e->getTraceAsString() . '</pre>';
    $response = new Response($content, 500);
    return $response;
  }

  /**
   * Handles a Request to convert it to a Response.
   *
   * When $catch is true, the implementation must catch all exceptions
   * and do its best to convert them to a Response instance.
   *
   * @param Request $request  A Request instance
   * @param integer $type     The type of the request
   *                          (one of HttpKernelInterface::MASTER_REQUEST
   *                          or HttpKernelInterface::SUB_REQUEST)
   * @param Boolean $catch    Whether to catch exceptions or not
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
    try
    {
      //Ensure we are working with a Cubex Request for added functionality
      if($request instanceof CubexRequest)
      {
        $this->instance('request', $request);
      }
      else
      {
        throw new \InvalidArgumentException(
          'You must use a \Cubex\Http\Request'
        );
      }

      //Fix anything that hasnt been set by the projects bootstrap
      $this->prepareCubex();

      return new CubexResponse("Hello Cubex");
    }
    catch(\Exception $e)
    {
      if($catch)
      {
        return $this->exceptionResponse($e);
      }
      else
      {
        throw $e;
      }
    }
  }

  /**
   * Terminates a request/response cycle.
   *
   * Should be called after sending the response and before
   * shutting down the kernel.
   *
   * @param Request  $request  A Request instance
   * @param Response $response A Response instance
   *
   * @api
   */
  public function terminate(Request $request, Response $response)
  {
    //Shutdown Cubex
  }
}
