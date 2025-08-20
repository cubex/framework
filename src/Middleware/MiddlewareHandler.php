<?php
namespace Cubex\Middleware;

use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareHandler implements Handler
{
  const PREPEND = 0;
  const APPEND = 1;

  /** @var \Cubex\Middleware\MiddlewareInterface[] */
  protected array $_middlewares = [];
  /**
   * @var \Packaged\Routing\Handler\Handler
   */
  protected Handler $_handler;

  public function __construct(Handler $handler)
  {
    $this->_handler = $handler;
  }

  public function add(MiddlewareInterface $middleware, int $addMode = self::APPEND)
  {
    if ($addMode)
    {
      $this->_middlewares[] = $middleware;
    }
    else
    {
      $this->_middlewares = [$middleware] + $this->_middlewares;
    }

    return $this;
  }

  public function handle(Context $c): Response
  {
    $handler = $this->_handler;
    if($this->_middlewares)
    {
      foreach($this->_middlewares as $middleware)
      {
        $handler = $middleware->setNext($handler);
      }
    }

    return $handler->handle($c);
  }
}
