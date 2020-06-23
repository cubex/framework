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

  protected function _attemptLanguages()
  {
    return array_unique(array_merge($this->request()->getLanguages(), ['en']));
  }

  public function prepareTranslator($path = '/translations/', $withUpdater = false)
  {
    $transDir = $this->getProjectRoot() . $path;
    $catalog = null;
    $language = 'en';

    $langs = $withUpdater ?
      [strtolower(substr($this->request()->getPreferredLanguage(), 0, 2))] : $this->_attemptLanguages();

    foreach($langs as $language)
    {
      $transFile = $transDir . $language . '.php';
      if(file_exists($transFile))
      {
        $catalog = DynamicArrayCatalog::fromFile($transFile);
        break;
      }
    }

    if($catalog === null)
    {
      $catalog = new DynamicArrayCatalog([]);
    }

    if($withUpdater)
    {
      $this->getCubex()->share(Translator::class, new TranslationLogger(new CatalogTranslator($catalog)));
      $this->getCubex()->retrieve(
        TranslationUpdater::class,
        [$this->getCubex(), $catalog, $transDir . $language . '.php', $language]
      );
    }
    else
    {
      $this->getCubex()->share(Translator::class, new CatalogTranslator($catalog));
    }
  }
}
