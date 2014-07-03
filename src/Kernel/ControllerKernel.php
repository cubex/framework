<?php
namespace Cubex\Kernel;

use Cubex\I18n\TranslationTrait;

class ControllerKernel extends CubexKernel
{
  use TranslationTrait;

  public function subRouteTo()
  {
    return [
      '%s\%sController',
      '%sController',
      'Views\%sView',
      'Views\%s',
      '%sView',
      '%s',
    ];
  }
}
