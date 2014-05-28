<?php
namespace Cubex\Kernel;

use Cubex\Http\Response;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiKernel extends CubexKernel
{
  public function subRouteTo()
  {
    return [
      '%s',
      '%sController',
      '%s\%sController',
      class_shortname($this) . '\%s'
    ];
  }

  /**
   * @inheritdoc
   */
  public function handle(
    Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    //Call start time to track performance
    $callStart = microtime(true);

    //Setup the error object
    $apiResponse                 = new \stdClass();
    $apiResponse->error          = new \stdClass();
    $apiResponse->error->message = '';
    $apiResponse->error->code    = 200;

    try
    {
      $response = parent::handle($request, $type, false);

      if($response instanceof Response)
      {
        //Retrieve the original object from the response
        $apiResponse->result = $response->getOriginalResponse();
      }
      else
      {
        //Shouldn't happen, but just incase
        $apiResponse->result = $response->getContent();
      }
    }
    catch(\Exception $e)
    {
      //Take the exception code as the http error code,
      //assuming 500 if not available
      $apiResponse->error->code = $e->getCode();
      if($apiResponse->error->code < 1)
      {
        $apiResponse->error->code = 500;
      }

      //Let the end user known the exception message
      $apiResponse->error->message = $e->getMessage();

      //If the call failed, no result should be present
      $apiResponse->result = null;
    }

    //Output call performance
    $apiResponse->profile                = new \stdClass();
    $apiResponse->profile->executionTime = number_format(
      (microtime(true) - $callStart) * 1000,
      3
    );

    //Track the call time from the start of the process when available
    if(defined('PHP_START'))
    {
      $apiResponse->profile->totalExecutionTime = number_format(
        (microtime(true) - PHP_START) * 1000,
        3
      );
    }

    //Return a json response
    $response = new Response();

    //Allow child classes to add additional data to the response e.g. user data
    return $response->fromJson($this->_finaliseApiResponse($apiResponse));
  }

  /**
   * Hook for finalising the api response
   *
   * @param $apiResponse
   *
   * @return mixed
   */
  protected function _finaliseApiResponse($apiResponse)
  {
    //Default action - do nothing
    return $apiResponse;
  }
}
