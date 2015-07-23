<?php
namespace Cubex\Kernel;

use Cubex\Http\Response;
use Packaged\Api\Exceptions\ApiException;
use Packaged\Api\Format\JsonFormat;
use Packaged\Api\Interfaces\ApiResponseInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class EndpointKernel extends CubexKernel
{
  /**
   * @inheritdoc
   */
  protected function _processResponse(
    $value, Request $request, $type = self::MASTER_REQUEST, $catch = true,
    $params = []
  )
  {
    if($value instanceof ApiResponseInterface)
    {
      $format = new JsonFormat();
      $value = Response::create(
        $format->encode(
          $value->toArray(),
          200,
          '',
          '\\' . get_class($value)
        )
      );
      $value->headers->set("Content-Type", "application/json");
    }

    return parent::_processResponse($value, $request, $type, $catch, $params);
  }

  /**
   * @inheritdoc
   */
  public function handleException(\Exception $exception)
  {
    if(!($exception instanceof ApiException))
    {
      $exception = new ApiException(
        $exception->getMessage(), $exception->getCode()
      );
    };

    //Let the end user known the exception message
    $apiResponse = Response::create($exception->getFormatted(new JsonFormat()));
    $apiResponse->headers->set("Content-Type", "application/json");
    return $apiResponse;
  }
}
