<?php
namespace Cubex;

use Cubex\Facade\FacadeLoader;
use Cubex\Kernel\CubexKernel;
use Cubex\ServiceManager\ServiceManager;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Packaged\Config\ConfigProviderInterface;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use \Cubex\Http\Request as CubexRequest;
use \Cubex\Http\Response as CubexResponse;

/**
 * Cubex Container, to be passed around for dependency injection etc
 */
class Cubex extends Container
  implements HttpKernelInterface, TerminableInterface
{
  const FLAG_CLI = 'cli';
  const FLAG_WEB = 'web';

  protected $_flags;
  protected $_docRoot;
  protected $_projectRoot;

  /**
   * @param null $webRoot
   */
  public function __construct($webRoot = null)
  {
    if($webRoot !== null)
    {
      $this->_docRoot = $webRoot;
    }

    $this->setDefaultFlags();
  }

  /**
   * Get the document root (usually your public folder)
   *
   * @return string
   */
  public function getDocRoot()
  {
    return $this->_docRoot;
  }

  /**
   * Get the base path of the project (level above public)
   *
   * @return string
   */
  public function getProjectRoot()
  {
    if($this->_projectRoot === null)
    {
      $this->_projectRoot = dirname($this->_docRoot);
    }
    return $this->_projectRoot;
  }

  public function setDefaultFlags()
  {
    $isCli = php_sapi_name() === 'cli';
    $this->setFlag(self::FLAG_CLI, $isCli);
    $this->setFlag(self::FLAG_WEB, !$isCli);
    return $this;
  }

  /**
   * Check for a flag
   *
   * @param string $flag
   *
   * @return bool
   */
  public function hasFlag($flag)
  {
    return isset($this->_flags[$flag]);
  }

  /**
   * Create a flag
   *
   * @param string $flag
   * @param bool   $enabled
   *
   * @return $this
   */
  public function setFlag($flag, $enabled = true)
  {
    if($enabled === true)
    {
      $this->_flags[$flag] = true;
    }
    else
    {
      $this->unsetFlag($flag);
    }
    return $this;
  }

  /**
   * Remove a flag
   *
   * @param string $flag
   *
   * @return $this
   */
  public function unsetFlag($flag)
  {
    unset($this->_flags[$flag]);
    return $this;
  }

  /**
   * Configure Cubex
   *
   * @param ConfigProviderInterface $conf
   *
   * @return $this
   */
  public function configure(ConfigProviderInterface $conf)
  {
    $this->instance('ConfigProvider', $conf);
    return $this;
  }

  /**
   * Retrieve the Cubex configuration
   *
   * @return ConfigProviderInterface|null
   */
  public function getConfiguration()
  {
    try
    {
      return $this->make("ConfigProvider");
    }
    catch(\Exception $e)
    {
      return null;
    }
  }

  /**
   * Automatically build any missing elements, such as configurations
   */
  public function prepareCubex()
  {
    if(!$this->bound("ConfigProvider"))
    {
      $config = new IniConfigProvider();
      $files  = ['defaults.ini'];

      foreach($files as $fileName)
      {
        $file = build_path($this->getProjectRoot(), 'conf', $fileName);
        try
        {
          $config->loadFile($file);
        }
        catch(\Exception $e)
        {
        }
      }

      $this->instance("ConfigProvider", $config);
    }
  }

  /**
   * Process configuration to bind services, interfaces etc
   *
   * @param ConfigProviderInterface $conf
   */
  public function processConfiguration(ConfigProviderInterface $conf)
  {
    CubexDefaultConfiguration::processConfiguration($this, $conf);
  }

  /**
   * Bind config item if not already defined
   *
   * @param ConfigProviderInterface $conf
   * @param                         $abstract
   * @param                         $section
   * @param                         $item
   * @param                         $default
   */
  public function bindFromConfigIf(
    ConfigProviderInterface $conf, $abstract, $section, $item, $default
  )
  {
    $class = $conf->getItem($section, $item, $default);
    if($class !== null)
    {
      $this->bindIf($abstract, $class, true);
    }
  }

  /**
   * Boot Cubex, Setup Facades, & Service Providers
   */
  public function boot()
  {
    //Setup facades
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this);
    FacadeLoader::register();

    //Setup Service Providers
    $serviceManager = new ServiceManager();
    $serviceManager->setCubex($this);
    $serviceManager->boot();
    $this->instance('service.manager', $serviceManager);
  }

  /**
   * Convert an exception into a beautiful html response
   *
   * @param \Exception $exception
   *
   * @return Http\Response
   */
  public function exceptionResponse(\Exception $exception)
  {
    $content = '<h1>Uncaught Exception</h1>';
    $content .= '<h2>(' . $exception->getCode() . ') ';
    $content .= $exception->getMessage() . '</h2>';

    //If we have a cubex exception, lets provide some debug data
    if($exception instanceof CubexException)
    {
      $content .= '<h3>Debug Data</h3>';
      ob_start();
      var_dump($exception->getDebug());
      $debugData = ob_get_clean();

      $content .= '<pre>' . $debugData . '</pre>';
      $content .= '<hr/>';
      $content .= '<h3>Stack Trace</h3>';
    }

    $content .= '<pre>' . $exception->getTraceAsString() . '</pre>';
    $response = new CubexResponse($content, 500);
    return $response;
  }

  /**
   * @inhreitdoc
   */
  public function handle(
    Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    try
    {
      //Ensure all constants have been configured
      if($this->getDocRoot() === null)
      {
        throw new \RuntimeException(
          "Cubex has been constructed without a document root provided" .
          ", you must call createConstants before calling handle."
        );
      }

      //Ensure we are working with a Cubex Request for added functionality
      if(!($request instanceof CubexRequest))
      {
        throw new \InvalidArgumentException(
          'You must use a \Cubex\Http\Request'
        );
      }

      $this->instance('request', $request);

      //Fix anything that hasnt been set by the projects bootstrap
      $this->prepareCubex();

      //Bind services
      $this->processConfiguration($this->getConfiguration());

      //Boot Cubex
      $this->boot();

      //Retrieve the
      $kernel = $this->makeWithCubex('\Cubex\Kernel\CubexKernel');
      if($kernel instanceof CubexKernel)
      {
        $response = $kernel->handle($request, $type, false);

        if(!($response instanceof Response))
        {
          throw CubexException::debugException(
            "A valid response was not generated by the default kernel",
            500,
            $response
          );
        }

        return $response;
      }

      throw new \RuntimeException("No Cubex Kernel has been configured");
    }
    catch(\Exception $e)
    {
      if($catch)
      {
        return $this->exceptionResponse($e);
      }
      else
      {
        throw $e;
      }
    }
  }

  /**
   * @inhreitdoc
   */
  public function terminate(Request $request, Response $response)
  {
    //Shutdown All Registered Services
    if($this->bound('service.manager'))
    {
      $serviceManager = $this->make('service.manager');
      if($serviceManager instanceof ServiceManager)
      {
        $serviceManager->shutdown();
      }
    }
  }

  /**
   * Resolve the given type from the container, and bind Cubex
   *
   * @param  string $abstract
   * @param  array  $parameters
   *
   * @return mixed
   */
  public function makeWithCubex($abstract, $parameters = array())
  {
    $item = $this->make($abstract, $parameters);
    if($item instanceof ICubexAware)
    {
      $item->setCubex($this);
    }
    return $item;
  }

  /**
   * @return string
   */
  public function env()
  {
    return 'dev';
  }
}
