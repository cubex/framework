<?php
namespace Cubex\Http\Visitor;

use Packaged\Config\ConfigSectionInterface;

class MockVisitorInfo implements IVisitorInfo
{
  protected $_country;
  protected $_city;
  protected $_region;

  public function __construct($country, $city, $region)
  {
    $this->_country = $country;
    $this->_city    = $city;
    $this->_region  = $region;
  }

  /**
   * Set the client IP to analyse
   *
   * @param $ip
   *
   * @return IVisitorInfo
   */
  public function setClientIp($ip)
  {
    return $this;
  }

  /**
   * @param ConfigSectionInterface $config
   *
   * @return IVisitorInfo
   */
  public function configure(ConfigSectionInterface $config)
  {
    return $this;
  }

  /**
   * Country from which the request originated,
   * as an ISO 3166-1 alpha-2 country code.
   *
   * @return string
   * @throws \Exception
   */
  public function getCountry()
  {
    return $this->_country;
  }

  /**
   * Name of the city from which the request originated.
   * For example, a request from the city of Mountain View
   * might have the header value mountain view.
   *
   * @return string
   * @throws \Exception
   */
  public function getCity()
  {
    return $this->_city;
  }

  /**
   * Name of region from which the request originated.
   * This value only makes sense in the context of the country.
   * For example, if the country is "US" and the region
   * is "ca", that "ca" means "California", not Canada.
   *
   * @return string
   * @throws \Exception
   */
  public function getRegion()
  {
    return $this->_region;
  }
}
