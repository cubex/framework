<?php
namespace Cubex\Context;

use Cubex\Cubex;
use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Cubex\Events\Handle\ResponsePreSendHeadersEvent;
use Cubex\I18n\TranslationUpdater;
use Packaged\ContextI18n\I18nContext;
use Packaged\Helpers\Timer;
use Packaged\Http\Headers\ServerTiming;
use Packaged\Http\Response;
use Packaged\I18n\Catalog\ArrayCatalog;
use Packaged\I18n\Catalog\DynamicArrayCatalog;
use Packaged\I18n\Catalog\MessageCatalog;
use Packaged\I18n\Translators\CatalogTranslator;
use Packaged\I18n\Translators\TranslationLogger;
use Packaged\I18n\Translators\Translator;
use Psr\Log\LoggerInterface;

class Context extends I18nContext implements CubexAware
{
  use CubexAwareTrait
  {
    setCubex as setCubexRaw;
  }

  protected $_timings = [];
  protected $_timers = [];

  /** @deprecated - Please switch to using newTimer */
  public function addTiming($key, $duration, $description = "")
  {
    $this->_timings[] = ['k' => $key, 'd' => $duration, 't' => $description];
    return $this;
  }

  public function newTimer(string $key, string $description = ''): Timer
  {
    $timer = new Timer($key);
    $timer->setDescription($description);
    $this->_timers[$key] = $timer;
    return $timer;
  }

  public function setCubex(Cubex $cubex)
  {
    $this->setCubexRaw($cubex);
    $cubex->listen(
      ResponsePreSendHeadersEvent::class,
      function (ResponsePreSendHeadersEvent $e) {
        $response = $e->getResponse();
        if(($this->_timings || $this->_timers) && $response instanceof Response)
        {
          $timing = new ServerTiming();
          foreach($this->_timings as $i => $tdata)
          {
            $timing->add($i . '-' . $tdata['k'], $tdata['d'], $tdata['t']);
          }
          /** @var Timer $timer */
          foreach($this->_timers as $timer)
          {
            $timing->add($timer->key(), floor($timer->duration() * 1000), $timer->description());
          }
          $response->addHeader($timing);
        }
      }
    );
    return $this;
  }

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

    $cubex = $this->getCubex();

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

    $cubex->share(MessageCatalog::class, $catalog);

    if($withUpdater)
    {
      //Push all translations via a translation logger
      $translationLogger = new TranslationLogger(new CatalogTranslator($catalog));
      $cubex->share(Translator::class, $translationLogger);

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
      $cubex->retrieve(
        TranslationUpdater::class,
        [$cubex, $tplCatalog, $catFile, static::DEFAULT_LANGUAGE, $translationLogger]
      );
    }
    else
    {
      $cubex->share(Translator::class, new CatalogTranslator($catalog));
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
