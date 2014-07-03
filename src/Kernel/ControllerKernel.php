<?php
namespace Cubex\Kernel;

use Cubex\I18n\TranslationTrait;

class ControllerKernel extends CubexKernel
{
  use TranslationTrait;

  public function subRouteTo()
  {
    $names = explode('\\', get_called_class());
    $class = ucwords(preg_replace('/Controller$/', '', array_pop($names)));

    return [
      $class . '\%s\%sController',
      $class . '\%s\%s',
      $class . '\%sController',
      $class . '\%s',
      '%s\%sController',
      '%sController',
      'Views\%sView',
      'Views\%s',
      '%sView',
      '%s',
    ];
  }
}
