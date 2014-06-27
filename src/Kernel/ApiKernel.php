<?php
namespace Cubex\Kernel;

use Cubex\Http\Response;
use Cubex\Responses\ApiResponse;
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

    //Setup the response object
    $apiResponse = $this->_createApiResponse();

    try
    {
      $response = parent::handle($request, $type, false);

      if($response instanceof ApiResponse)
      {
        return $response;
      }
      else if($response instanceof Response)
      {
        //Retrieve the original object from the response
        $apiResponse->setContent($response->getOriginalResponse());
      }
      else
      {
        //Shouldn't happen, but just incase
        $apiResponse->setContent($response->getContent());
      }
    }
    catch(\Exception $e)
    {
      if($catch)
      {
        //Let the end user known the exception message
        $apiResponse->setStatus($e->getMessage(), $e->getCode());
      }
      else
      {
        throw $e;
      }
    }

    //Allow child classes to add additional data to the response e.g. user data
    return $this->_finaliseApiResponse($apiResponse);
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

  /**
   * Construct a new API response
   *
   * Allow for initialisation on response objects
   *
   * @return ApiResponse
   */
  protected function _createApiResponse()
  {
    return new ApiResponse();
  }
}
