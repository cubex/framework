<?php
namespace Cubex\Facade;


class Session extends Facade
{
  protected static function getFacadeAccessor()
  {
    return 'session';
  }

}
