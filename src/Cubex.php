<?php
namespace Cubex;

use Cubex\Facade\FacadeLoader;
use Cubex\Kernel\CubexKernel;
use Cubex\ServiceManager\ServiceManager;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Packaged\Config\ConfigProviderInterface;
use Packaged\Config\Provider\Ini\IniConfigProvider;
use Packaged\Helpers\Path;
use Packaged\Helpers\System;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

  protected $_env;
  protected $_flags;
  protected $_docRoot;
  protected $_projectRoot;
  protected $_booted = false;

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
    $isCli = !System::isFunctionDisabled('php_sapi_name')
      && php_sapi_name() === 'cli';
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
      $files = [
        'defaults.ini',
        'defaults' . DIRECTORY_SEPARATOR . 'config.ini',
        $this->env() . '.ini',
        $this->env() . DIRECTORY_SEPARATOR . 'config.ini',
      ];

      foreach($files as $fileName)
      {
        $file = Path::build($this->getProjectRoot(), 'conf', $fileName);
        try
        {
          $config->loadFile($file, true);
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
    if($this->_booted)
    {
      return null;
    }

    //Fix anything that hasnt been set by the projects bootstrap
    $this->prepareCubex();

    //Bind services
    $this->processConfiguration($this->getConfiguration());

    //Setup facades
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this);
    FacadeLoader::register();

    //Setup Service Providers
    $serviceManager = new ServiceManager();
    $serviceManager->setCubex($this);
    $serviceManager->boot();
    $this->instance('service.manager', $serviceManager);

    $this->_booted = true;
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
    $response = new CubexResponse(self::exceptionAsString($exception), 500);
    return $response;
  }

  public static function exceptionAsString(\Exception $exception)
  {
    $content = '<div style="font-family:calibri,arial; font-size:14px;">';
    $content .= '<h1>An uncaught exception was thrown</h1>';
    $content .= '<h2 style="color:#B20000;">(' . $exception->getCode() . ') ';
    $content .= $exception->getMessage() . '</h2>';

    //If we have a cubex exception, lets provide some debug data
    if($exception instanceof CubexException && $exception->getDebug() !== null)
    {
      $content .= '<h3 style="color:#B20000;">Cubex Debug Data</h3>';
      $content .= '<div style="padding:10px;background:#E1E9F5; ';
      $content .= 'border:1px solid #333333;">';

      $debug = $exception->getDebug();
      if(is_string($debug))
      {
        $debug = trim($debug);
        $content .= nl2br($debug);
      }
      else if(is_array($debug) || is_object($debug))
      {
        $content .= '<pre>';
        $content .= print_r($debug, true);
        $content .= '</pre>';
      }
      else
      {
        $content .= '<pre>';
        ob_start();
        var_dump($debug);
        $content .= ob_get_clean();
        $content .= '</pre>';
      }

      $content .= '</div>';
      $content .= '<hr/>';
      $content .= '<h3>Stack Trace</h3>';
    }

    $content .= '<div style="padding:10px;background:#FAF7E7;';
    $content .= 'border:1px solid #333333;line-height: 25px;">';
    $content .= nl2br($exception->getTraceAsString()) . '</div>';
    $content .= '</div>';
    return $content;
  }

  /**
   * @param Request $request
   * @param int     $type
   * @param bool    $catch
   *
   * @return CubexResponse|BinaryFileResponse|Response
   * @throws \Exception
   */
  public function handle(
    Request $request, $type = self::MASTER_REQUEST, $catch = true
  )
  {
    //If the favicon has not been picked up within the public folder
    //return the cubex favicon

    if($request->getRequestUri() === '/favicon.ico')
    {
      $favIconPaths = [];
      $favIconPaths[] = Path::build($this->getProjectRoot(), 'favicon.ico');
      $favIconPaths[] = Path::build(
        $this->getProjectRoot(),
        'assets',
        'favicon.ico'
      );
      $favIconPaths[] = Path::build(dirname(__DIR__), 'favicon.ico');
      $favPath = null;

      foreach($favIconPaths as $favPath)
      {
        if(file_exists($favPath))
        {
          break;
        }
      }

      $favicon = new BinaryFileResponse($favPath);
      $favicon->prepare($request);
      return $favicon;
    }

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

      //Boot Cubex
      $this->boot();

      //Retrieve the
      $kernel = $this->makeWithCubex('\Cubex\Kernel\CubexKernel');
      if($kernel instanceof CubexKernel)
      {
        $response = $kernel->handle($request, $type, $catch);

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
   * @param Request  $request
   * @param Response $response
   */
  public function terminate(Request $request, Response $response)
  {
    //Shutdown
    $this->shutdown();
  }

  /**
   * Shutdown Cubex
   */
  public function shutdown()
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
  public function makeWithCubex($abstract, $parameters = [])
  {
    $item = $this->make($abstract, $parameters);
    if($item instanceof ICubexAware)
    {
      $item->setCubex($this);
    }
    return $item;
  }

  /**
   * Retrieve the current environment name e.g. local, development, production
   *
   * @return string
   */
  public function env()
  {
    if($this->_env !== null)
    {
      return $this->_env;
    }

    $this->_env = getenv('CUBEX_ENV'); // Apache Config

    if(($this->_env === null || !$this->_env) && isset($_ENV['CUBEX_ENV']))
    {
      $this->_env = $_ENV['CUBEX_ENV'];
    }

    if($this->_env === null || !$this->_env)
    {
      //If there is no environment available, assume local
      $this->_env = 'local';
    }

    return $this->_env;
  }

  /**
   * Set the environment name
   *
   * @param $env
   *
   * @return $this
   */
  public function setEnv($env)
  {
    $this->_env = $env;
    return $this;
  }
}
