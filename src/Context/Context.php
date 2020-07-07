<?php
namespace Cubex\Context;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\I18n\TranslationUpdater;
use Packaged\I18n\Catalog\ArrayCatalog;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Translators\CatalogTranslator;
use Packaged\I18n\Translators\TranslationLogger;
use Packaged\I18n\Translators\Translator;
use Psr\Log\LoggerInterface;

class Context extends \Packaged\Context\Context implements CubexAware
{
  use CubexAwareTrait;

  const DEFAULT_LANGUAGE = 'en';
  protected $_language = self::DEFAULT_LANGUAGE;

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
      $this->_initialized;
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

  /**
   * Visitors preferred languages
   *
   * @return array
   */
  protected function _preferredLanguages(): array
  {
    return [$this->request()->getPreferredLanguage()];
  }

  /**
   * Languages supported by the visitor
   *
   * @return array
   */
  protected function _attemptLanguages()
  {
    return array_unique(
      array_merge($this->_preferredLanguages(), $this->request()->getLanguages(), [static::DEFAULT_LANGUAGE])
    );
  }

  /**
   * Current displayed language (this is set AFTER prepare translator is called)
   *
   * @return string
   */
  public function currentLanguage()
  {
    return $this->_language;
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
}
