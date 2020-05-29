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

    foreach($this->_attemptLanguages() as $language)
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
      $useLang = strtolower(substr($this->request()->getPreferredLanguage(), 0, 2));
      $this->getCubex()->share(Translator::class, new TranslationLogger(new CatalogTranslator($catalog)));
      $this->getCubex()->retrieve(
        TranslationUpdater::class,
        [$this->getCubex(), $catalog, $transDir . $useLang . '.php', $useLang]
      );
    }
    else
    {
      $this->getCubex()->share(Translator::class, new CatalogTranslator($catalog));
    }
  }
}
