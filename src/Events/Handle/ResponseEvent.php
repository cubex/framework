<?php
namespace Cubex\Events\Handle;

use Cubex\Context\Context;
use Cubex\Http\Handler;
use Symfony\Component\HttpFoundation\Response;

abstract class ResponseEvent extends HandlerEvent
{
  private $_response;

  public static function i(Context $context, Handler $handler = null, Response $response = null)
  {
    $event = parent::i($context, $handler);
    $event->_response = $response;
    return $event;
  }

  public function getResponse(): Response
  {
    return $this->_response;
  }

}
