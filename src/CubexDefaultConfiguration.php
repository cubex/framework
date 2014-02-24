<?php
namespace Cubex;

use Packaged\Config\ConfigProviderInterface;

class CubexDefaultConfiguration
{
  /**
   * Process configuration to bind services, interfaces etc
   *
   * @param Cubex                   $cubex
   * @param ConfigProviderInterface $conf
   */
  public static function processConfiguration(
    Cubex $cubex, ConfigProviderInterface $conf
  )
  {

    //Abstract, section, value, default
    $defaults   = array();
    $defaults[] = ['\Cubex\Kernel\CubexKernel', "kernel", "default", null];
    //$defaults[] = ['log', "logging", 'logger', '\Illuminate\Log\Writer'];
    $defaults[] = [
      '\Cubex\Routing\IRouter',
      "routing",
      "router",
      '\Cubex\Routing\Router'
    ];
    $defaults[] = ['404', "errors", "404", '\Cubex\Responses\Error404Response'];

    foreach($defaults as $item)
    {
      $cubex->bindFromConfigIf($conf, $item[0], $item[1], $item[2], $item[3]);
    }
  }
}
