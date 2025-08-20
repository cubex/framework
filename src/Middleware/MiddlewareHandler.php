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

  /** @var \Packaged\Routing\Handler\Handler */
  protected Handler $_handler;

  /** @var int $_addMode */
  protected int $_addMode;

  public function __construct(Handler $handler)
  {
    $this->_handler = $handler;
  }

  public function prependMode(): self
  {
    $this->_addMode = self::PREPEND;
    return $this;
  }

  public function appendMode(): self
  {
    $this->_addMode = self::APPEND;
    return $this;
  }

  public function add(MiddlewareInterface $middleware, ?int $addMode = null)
  {
    if (!isset($addMode))
    {
      $addMode = $this->_addMode;
    }

    if ($addMode === self::PREPEND)
    {
      $this->_middlewares = [$middleware] + $this->_middlewares;
    }
    else
    {
      $this->_middlewares[] = $middleware;
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
