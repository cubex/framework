<?php
namespace Cubex\Context;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\I18n\TranslationUpdater;
use Packaged\ContextI18n\I18nContext;
use Packaged\I18n\Catalog\ArrayCatalog;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Translators\CatalogTranslator;
use Packaged\I18n\Translators\TranslationLogger;
use Packaged\I18n\Translators\Translator;
use Psr\Log\LoggerInterface;

class Context extends I18nContext implements CubexAware
{
  use CubexAwareTrait;

  protected function _construct()
  {
    parent::_construct();
    $this->request()->defineTlds(['cubex-local.com', 'local-host.xyz'], true);
  }

  private $_initialized;

  final public function initialize()
  {
    if(!$this->_initialized)
    {
      $this->_initialized = true;
      $this->_initialize();
    }
    return $this;
  }

  protected function _initialize()
  {
    //Called when ready to start using the context
  }

  public function log(): LoggerInterface
  {
    return $this->getCubex()->getLogger();
  }

  public function prepareTranslator($path = '/translations/', $withUpdater = false)
  {
    $transDir = $this->getProjectRoot() . $path;
    $catalog = new ArrayCatalog([]);

    foreach($this->_attemptLanguages() as $language)
    {
      $transFile = $transDir . $language . '.php';
      if(file_exists($transFile))
      {
        $this->_language = $language;
        $catalog = ArrayCatalog::fromFile($transFile);
        break;
      }
    }

    if($withUpdater)
    {
      //Push all translations via a translation logger
      $this->getCubex()->share(Translator::class, new TranslationLogger(new CatalogTranslator($catalog)));

      //Keep track of all new translations within _tpl.php
      $catFile = $transDir . '_tpl.php';
      if(file_exists($catFile))
      {
        //Load the existing template
        $tplCatalog = DynamicArrayCatalog::fromFile($catFile);
      }
      else
      {
        $tplCatalog = new DynamicArrayCatalog([]);
      }

      //Setup the translation logger to listen @ shutdown
      $this->getCubex()->retrieve(
        TranslationUpdater::class,
        [$this->getCubex(), $tplCatalog, $catFile, static::DEFAULT_LANGUAGE]
      );
    }
    else
    {
      $this->getCubex()->share(Translator::class, new CatalogTranslator($catalog));
    }
  }

  protected function _translator(): Translator
  {
    return $this->getCubex()->retrieve(Translator::class);
  }

  public function translator(): Translator
  {
    return $this->_translator();
  }

}
