<?php
namespace Cubex\Http\Visitor;

use GeoIp2\Database\Reader;
use GeoIp2\WebService\Client;
use Packaged\Config\ConfigSectionInterface;
use Packaged\Helpers\ValueAs;

class MaxmindVisitor implements IVisitorInfo
{
  protected $_record;
  protected $_ip;
  /**
   * @var ConfigSectionInterface
   */
  protected $_config;

  /**
   * @var Reader|Client
   */
  protected $_reader;

  public function configure(ConfigSectionInterface $config)
  {
    $this->_config = $config;
    $mode          = $config->getItem('mode', 'reader');
    if($mode == 'reader')
    {
      $this->_reader = new Reader(
        $config->getItem(
          'database',
          new \Exception('No GeoIP Database defined')
        )
      );
    }
    else if($mode == 'client')
    {
      $this->_reader = new Client(
        $config->getItem(
          'user_id',
          new \Exception("No maxmind user id specified")
        ),
        $config->getItem(
          'licence_key',
          new \Exception("No maxmind licence key specified")
        ),
        ValueAs::arr($config->getItem('locales', 'en')),
        $config->getItem('host', 'geoip.maxmind.com')
      );
    }
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
    $this->_ip = $ip;
    try
    {
      $this->_record = $this->_reader->city($ip);
    }
    catch(\Exception $e)
    {
      $this->_record = null;
    }
  }

  protected function _getConfig($key, $default)
  {
    if($this->_config === null)
    {
      throw new \Exception("You must configure the MaxMindVisitor class");
    }

    return $this->_config->getItem($key, $default);
  }

  /**
   * Country from which the request originated,
   * as an ISO 3166-1 alpha-2 country code.
   *
   * @return string
   *
   * @throws \Exception
   */
  public function getCountry()
  {

    if($this->_record === null)
    {
      $country = '';
    }
    else
    {
      $country = (string)$this->_record->country->isoCode;
    }

    if(empty($country))
    {
      return $this->_getConfig("country", "GB");
    }

    return $country;
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
    if($this->_record === null)
    {
      $city = '';
    }
    else
    {
      $city = (string)$this->_record->city->name;
    }

    if(empty($city))
    {
      return $this->_getConfig("city", "London");
    }

    return $city;
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
    if($this->_record === null)
    {
      $region = '';
    }
    else
    {
      $region = (string)$this->_record->mostSpecificSubdivision->isoCode;
    }

    if(empty($region))
    {
      return $this->_getConfig("region", "eng");
    }

    return $region;
  }

  public function __destruct()
  {
    try
    {
      if($this->_reader instanceof Reader)
      {
        $this->_reader->close();
      }
    }
    catch(\Exception $e)
    {
    }
  }
}
