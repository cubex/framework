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

  public function __construct(Handler $handler)
  {
    $this->_handler = $handler;
  }

  public function prepend(MiddlewareInterface $middleware): self
  {
    return $this->add($middleware, self::PREPEND);
  }

  public function append(MiddlewareInterface $middleware): self
  {
    return $this->add($middleware, self::APPEND);
  }

  public function add(MiddlewareInterface $middleware, ?int $addMode = null): self
  {
    if ($addMode === self::PREPEND)
    {
      array_unshift($this->_middlewares, $middleware);
    }
    else
    {
      $this->_middlewares[] = $middleware;
    }

    return $this;
  }

  public function remove(MiddlewareInterface|string $remove): self
  {
    foreach ($this->_middlewares as $key => $middleware)
    {
      if ($middleware instanceof $remove || $middleware::class === $remove)
      {
        unset($this->_middlewares[$key]);
        break;
      }
    }

    $this->_middlewares = array_values($this->_middlewares);
    return $this;
  }

  public function replace(MiddlewareInterface|string $replace, MiddlewareInterface $with): self
  {
    foreach ($this->_middlewares as $key => $middleware)
    {
      if ($middleware instanceof $replace || $middleware::class === $replace)
      {
        $this->_middlewares[$key] = $with;
        break;
      }
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
