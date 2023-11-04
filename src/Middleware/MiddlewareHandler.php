<?php
namespace Cubex\Middleware;

use Packaged\Context\Context;
use Packaged\Routing\Handler\Handler;
use Symfony\Component\HttpFoundation\Response;

class MiddlewareHandler implements Handler
{
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

  public function add(MiddlewareInterface $middleware)
  {
    $this->_middlewares[] = $middleware;
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