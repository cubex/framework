<?php
namespace Cubex\I18n;

use Cubex\Cubex;
use Cubex\CubexAware;
use Packaged\Context\ContextAware;
use Packaged\I18n\Translatable;
use Packaged\I18n\Translators\Translator;

/** @deprecated */
trait GetTranslatorTrait
{
  protected function _getTranslator(): Translator
  {
    $cubex = null;
    if($this instanceof CubexAware)
    {
      $cubex = $this->getCubex();
    }
    else if($this instanceof ContextAware)
    {
      $ctx = $this->getContext();
      if($ctx instanceof CubexAware)
      {
        $cubex = $ctx->getCubex();
      }
    }

    if($cubex instanceof Cubex)
    {
      try
      {
        return new Translator($this->getCubex()->retrieve(Translatable::class), $this->currentLanguage());
      }
      catch(\Exception $e)
      {
      }
    }
    return new Translator();
  }
}
