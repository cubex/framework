<?php
namespace Cubex\Context;

use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Packaged\Config\ConfigProviderInterface;
use Packaged\Config\Provider\ConfigProvider;
use Packaged\Event\Channel\Channel;
use Packaged\Helpers\System;
use Packaged\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use function php_sapi_name;
use function uniqid;

class Context
{
  use CubexAwareTrait;
  const ENV_PHPUNIT = 'phpunit';
  const ENV_LOCAL = 'local';
  const ENV_DEV = 'dev';
  const ENV_QA = 'qa';
  const ENV_UAT = 'uat';
  const ENV_STAGE = 'stage';
  const ENV_PROD = 'prod';
  protected $_projectRoot;
  protected $_env;
  protected $_cfg;
  protected $_meta;
  protected $_routeData;
  private $_id;
  private $_events;
  private $_request;

  public final function __construct(Request $request = null)
  {
    // Give this context an ID
    $this->_id = $this->_generateId();

    $this->_request = $request ?: Request::createFromGlobals();
    $this->_meta = new ParameterBag();
    $this->_routeData = new ParameterBag();
    $this->_cfg = new ConfigProvider();
    $this->_events = new Channel('context');
    $this->_construct();
  }

  protected function _generateId()
  {
    return uniqid('ctx-', true);
  }

  protected function _construct()
  {
    //This method will be called after the context has been constructed
  }

  public function setCubex(Cubex $cubex)
  {
    $this->_cubex = $cubex;
    if($this->_env === null)
    {
      $this->setEnvironment($cubex->getSystemEnvironment());
    }
    return $this;
  }

  public function setEnvironment($env)
  {
    $this->_env = $env;
    return $this;
  }

  public function getProjectRoot()
  {
    return $this->_projectRoot;
  }

  public function setProjectRoot($root)
  {
    $this->_projectRoot = $root;
    return $this;
  }

  public function isEnv($env)
  {
    return $this->getEnvironment() === $env;
  }

  public function getEnvironment()
  {
    return $this->_env;
  }

  public function isCli()
  {
    return !System::isFunctionDisabled('php_sapi_name') && php_sapi_name() === 'cli';
  }

  /**
   * @param ConfigProviderInterface $config
   *
   * @return $this
   */
  public function setConfig(ConfigProviderInterface $config)
  {
    $this->_cfg = $config;
    return $this;
  }

  /**
   * @return ConfigProviderInterface
   */
  public function getConfig()
  {
    return $this->_cfg;
  }

  /**
   * Unique ID for this context
   *
   * @return string
   */
  public function id()
  {
    return $this->_id;
  }

  /**
   * @return Request
   */
  public function request()
  {
    return $this->_request;
  }

  /**
   * @return ParameterBag
   */
  public function meta()
  {
    return $this->_meta;
  }

  /**
   * @return ParameterBag
   */
  public function routeData()
  {
    return $this->_routeData;
  }

  /**
   * @return ConfigProviderInterface
   */
  public function config()
  {
    return $this->_cfg;
  }

  /**
   * Events channel
   *
   * @return Channel
   */
  public function events(): Channel
  {
    return $this->_events;
  }
}
