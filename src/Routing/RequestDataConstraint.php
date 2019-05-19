<?php
namespace Cubex\Routing;

use Packaged\Context\Context;

class RequestDataConstraint implements Condition
{
  protected $_values = [];

  const COOKIE = 'cookie';
  const QUERYSTRING = 'querystring';
  const POST = 'post';
  const SERVER = 'server';

  public function match(Context $context): bool
  {
    foreach($this->_values as $type => $values)
    {
      switch($type)
      {
        case self::POST:
          $data = $context->request()->request;
          break;
        case self::QUERYSTRING:
          $data = $context->request()->query;
          break;
        case self::COOKIE:
          $data = $context->request()->cookies;
          break;
        case self::SERVER:
          $data = $context->request()->server;
          break;
      }

      foreach($values as $key => $value)
      {
        if($value === null)
        {
          if(!$data->has($key))
          {
            return false;
          }
        }
        else if($data->get($key) !== $value)
        {
          return false;
        }
      }
    }
    return true;
  }

  public static function i()
  {
    return new static();
  }

  public function cookie(string $key, string $value = null)
  {
    $this->_values[self::COOKIE][$key] = $value;
    return $this;
  }

  public function query(string $key, string $value = null)
  {
    $this->_values[self::QUERYSTRING][$key] = $value;
    return $this;
  }

  public function post(string $key, string $value = null)
  {
    $this->_values[self::POST][$key] = $value;
    return $this;
  }

  public function server(string $key, string $value = null)
  {
    $this->_values[self::SERVER][$key] = $value;
    return $this;
  }
}
