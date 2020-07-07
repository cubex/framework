<?php
namespace Cubex\Context;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\I18n\TranslationUpdater;
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
    $catalog = new DynamicArrayCatalog([]);

    $language = static::DEFAULT_LANGUAGE;
    foreach($this->_attemptLanguages() as $language)
    {
      $transFile = $transDir . $language . '.php';
      if(file_exists($transFile))
      {
        $this->_language = $language;
        $catalog = DynamicArrayCatalog::fromFile($transFile);
        break;
      }
    }

    if($withUpdater)
    {
      $this->getCubex()->share(Translator::class, new TranslationLogger(new CatalogTranslator($catalog)));
      $this->getCubex()->retrieve(
        TranslationUpdater::class,
        [$this->getCubex(), $catalog, $transDir . '_tpl.php', $language]
      );
    }
    else
    {
      $this->getCubex()->share(Translator::class, new CatalogTranslator($catalog));
    }
  }
}
