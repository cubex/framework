<?php
namespace Cubex\Http;

class Request extends \Symfony\Component\HttpFoundation\Request
{
  protected $_domain;
  protected $_subdomain;
  protected $_tld;

  protected $_definedTlds = array();
  protected $_knownTlds = array(
    'co'  => 'co',
    'com' => 'com',
    'org' => 'org',
    'me'  => 'me',
    'gov' => 'gov',
    'net' => 'net',
    'edu' => 'edu'
  );

  /**
   * Define accepted TLDs for use when determining tlds
   *
   * @param array $tlds
   * @param bool  $append
   */
  public function defineTlds(array $tlds, $append = false)
  {
    $tlds = array_combine($tlds, $tlds);
    if($append)
    {
      $this->_definedTlds = array_merge($this->_definedTlds, $tlds);
    }
    else
    {
      $this->_definedTlds = $tlds;
    }
  }

  /**
   * Returns a list of user defined TLDs, used for calculating domain parts
   *
   * @return array
   */
  public function getDefinedTlds()
  {
    return array_keys($this->_definedTlds);
  }

  /**
   * Take the host string and split into subdomain , domain & tld
   *
   * @return $this
   */
  protected function _prepareHost()
  {
    $parts = array_reverse(explode('.', strtolower($this->getHost())));

    if(count($parts) == 1)
    {
      $this->_domain = $parts[0];
    }
    else
    {
      foreach($parts as $i => $part)
      {
        if(empty($this->_tld))
        {
          $this->_tld = $part;
          continue;
        }

        if(empty($this->_domain))
        {
          if($i < 2
            && (strlen($part) == 2
              || isset($this->_definedTlds[$part . '.' . $this->_tld])
              || isset($this->_knownTlds[$part])
            )
          )
          {
            $this->_tld = $part . '.' . $this->_tld;
          }
          else
          {
            $this->_domain = $part;
          }
          continue;
        }

        if(empty($this->_subdomain))
        {
          $this->_subdomain = $part;
        }
        else
        {
          $this->_subdomain = $part . '.' . $this->_subdomain;
        }
      }
    }

    return $this;
  }

  /**
   * http:// or https://
   *
   * @return string
   */
  public function protocol()
  {
    return $this->isSecure() ? 'https://' : 'http://';
  }

  /**
   * Sub Domain e.g. www.
   *
   * @return string|null
   */
  public function subDomain()
  {
    if($this->_subdomain === null)
    {
      $this->_prepareHost();
    }

    return $this->_subdomain;
  }

  /**
   * Main domain, excluding sub domains and tlds
   *
   * @return string
   */
  public function domain()
  {
    if($this->_domain === null)
    {
      $this->_prepareHost();
    }

    return $this->_domain;
  }

  /**
   * Top Level Domain
   *
   * @return string
   */
  public function tld()
  {
    if($this->_tld === null)
    {
      $this->_prepareHost();
    }

    return $this->_tld;
  }

  /**
   * Port number the user is requesting on
   *
   * @return int
   */
  public function port()
  {
    return $this->getPort();
  }

  /**
   * Returns a formatted string based on the url parts
   *
   * - %r = Port Number (no colon)
   * - %i = Path (leading slash)
   * - %p = Scheme with //: (Usually http:// or https://)
   * - %h = Host (Subdomain . Domain . Tld : Port [port may not be set])
   * - %d = Domain
   * - %s = Sub Domain
   * - %t = Tld
   *
   * @param string $format
   *
   * @return string mixed
   */
  public function urlSprintf($format = "%p%h")
  {
    $formater = [
      "%r" => $this->getPort(),
      "%i" => $this->getPathInfo(),
      "%p" => $this->protocol(),
      "%h" => $this->getHttpHost(),
      "%d" => $this->domain(),
      "%s" => $this->subDomain(),
      "%t" => $this->tld(),
    ];

    return str_replace(array_keys($formater), $formater, $format);
  }

  /**
   * Match the current request against domain criteria
   *
   * @param null $domain
   * @param null $tld
   * @param null $subDomain
   *
   * @return bool
   */
  public function matchDomain($domain = null, $tld = null, $subDomain = null)
  {
    $match = true;

    if($domain !== null && strtolower($domain) !== $this->domain())
    {
      $match = false;
    }

    if($tld !== null && strtolower($tld) !== $this->tld())
    {
      $match = false;
    }

    if($subDomain !== null && strtolower($subDomain) !== $this->subDomain())
    {
      $match = false;
    }

    return $match;
  }
}
