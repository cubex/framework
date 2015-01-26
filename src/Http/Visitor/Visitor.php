<?php
namespace Cubex\Http\Visitor;

use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\Http\Request;
use Cubex\ICubexAware;
use Packaged\Config\ConfigSectionInterface;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Helpers\System;

class Visitor implements IVisitorInfo, ICubexAware
{
  use CubexAwareTrait;

  /**
   * @var Request
   */
  protected $_request;
  protected $_ip;
  /**
   * @var IVisitorInfo
   */
  protected $_failover;

  protected $_country;
  protected $_city;
  protected $_region;

  protected $_config;

  public function __construct(
    Request $request, Cubex $cubex
  )
  {
    $this->setCubex($cubex);
    $this->_request = $request;
    $this->_ip = $this->_request->getClientIp();

    try
    {
      $this->_config = $cubex->getConfiguration()->getSection('http_visitor');
    }
    catch(\Exception $e)
    {
      $this->_config = new ConfigSection('http_visitor', []);
    }
    $this->configure($this->_config);

    if(System::isAppEngine($request->server->get('SERVER_SOFTWARE')))
    {
      $this->_fromAppEngine();
    }
    else if($request->server->get('GEOIP_ADDR', null) !== null)
    {
      $this->_fromModGeoIP();
    }
  }

  /**
   * Set the client IP to analyse
   *
   * @param $ip
   *
   * @return self
   */
  public function setClientIp($ip)
  {
    $this->_ip = $ip;
    return $this;
  }

  public function setFailoverLookup(IVisitorInfo $failover)
  {
    $this->_failover = $failover;
    return $this;
  }

  public function configure(ConfigSectionInterface $config)
  {
    $failover = $config->getItem("failover", null);

    if($failover !== null)
    {
      $this->_failover = $this->getCubex()->make($failover);

      if($this->_failover instanceof IVisitorInfo)
      {
        $this->_failover->configure($config);
        $this->_failover->setClientIp($this->_ip);
      }
    }
  }

  protected function _fromAppEngine()
  {
    $params = $this->_request->server;
    $this->_country = $params->get('HTTP_X_APPENGINE_COUNTRY', null);
    $this->_city = $params->get('HTTP_X_APPENGINE_CITY', null);
    $this->_region = $params->get('HTTP_X_APPENGINE_REGION', null);
  }

  protected function _fromModGeoIP()
  {
    $params = $this->_request->server;
    $this->_country = $params->get('GEOIP_COUNTRY_CODE', null);
    $this->_city = $params->get('GEOIP_CITY', null);
    $this->_region = $params->get('GEOIP_REGION_NAME', null);
  }

  protected function _fromWhois()
  {
    if(System::commandExists('whois'))
    {
      exec("whois " . $this->_ip, $whois);
      $whois = implode("\n", $whois);
      $countries = $cities = $regions = [];

      preg_match_all('/^country:\s*([A-Z]{2})/mi', $whois, $countries);
      if(isset($countries[1]) && !empty($countries[1]))
      {
        $this->_country = end($countries[1]);
      }

      preg_match_all('/^city:\s*([A-Z].*$)/mi', $whois, $cities);
      if(isset($cities[1]) && !empty($cities[1]))
      {
        $this->_city = end($cities[1]);
      }

      preg_match_all(
        '/^(state|stateprov|county|prov|province):\s*([A-Z].*$)/mi',
        $whois,
        $regions
      );

      if(isset($regions[2]) && !empty($regions[1]))
      {
        $this->_region = end($regions[2]);
      }
    }
  }

  /**
   * Country from which the request originated,
   * as an ISO 3166-1 alpha-2 country code.
   *
   * @return string
   */
  public function getCountry()
  {
    if($this->_country === null && $this->_failover !== null)
    {
      $this->_country = $this->_failover->getCountry();
    }

    if($this->_country === null && $this->_config->getItem('whois', false))
    {
      $this->_fromWhois();
    }

    if($this->_country === null)
    {
      $this->_country = $this->_config->getItem("country", "GB");
    }

    return $this->_country;
  }

  /**
   * Name of the city from which the request originated.
   * For example, a request from the city of Mountain View
   * might have the header value mountain view.
   *
   * @return string
   */
  public function getCity()
  {
    if($this->_city === null && $this->_failover !== null)
    {
      $this->_city = $this->_failover->getCity();
    }

    if($this->_city === null)
    {
      $this->_city = $this->_config->getItem("city", "London");
    }

    return $this->_city;
  }

  /**
   * Name of region from which the request originated.
   * This value only makes sense in the context of the country.
   * For example, if the country is "US" and the region
   * is "ca", that "ca" means "California", not Canada.
   *
   * @return string
   */
  public function getRegion()
  {
    if($this->_region === null && $this->_failover !== null)
    {
      $this->_region = $this->_failover->getRegion();
    }

    if($this->_region === null)
    {
      $this->_region = $this->_config->getItem("region", "eng");
    }

    return $this->_region;
  }
}
