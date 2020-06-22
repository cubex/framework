<?php
namespace Cubex\I18n;

use Cubex\Cubex;
use Cubex\CubexAware;
use Packaged\Context\ContextAware;
use Packaged\I18n\Translators\ReplacementsOnlyTranslator;
use Packaged\I18n\Translators\Translator;

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
        return $cubex->retrieve(Translator::class);
      }
      catch(\Exception $e)
      {
      }
    }
    return new ReplacementsOnlyTranslator();
  }
}
