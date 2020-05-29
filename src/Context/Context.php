<?php
namespace Cubex\Context;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Packaged\Http\Request;
use Psr\Log\LoggerInterface;

class Context extends \Packaged\Context\Context implements CubexAware
{
  use CubexAwareTrait;

  protected function _construct()
  {
    parent::_construct();
    $r = $this->request();
    if($r instanceof Request)
    {
      $r->defineTlds(['cubex-local.com', 'local-host.xyz'], true);
    }
  }

  public function log(): LoggerInterface
  {
    return $this->getCubex()->getLogger();
  }
}
