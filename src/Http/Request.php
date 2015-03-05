<?php
namespace Cubex\Http;

use Packaged\Helpers\FQDN;

/**
 * @method static Request createFromGlobals
 */
class Request extends \Symfony\Component\HttpFoundation\Request
{
  /**
   * @var FQDN
   */
  protected $_domain;
  protected $_partCache = [];

  /**
   * @inheritdoc
   */
  public function __construct(
    array $query = [], array $request = [],
    array $attributes = [], array $cookies = [],
    array $files = [], array $server = [], $content = null
  )
  {
    parent::__construct(
      $query,
      $request,
      $attributes,
      $cookies,
      $files,
      $server,
      $content
    );
    $this->setLocale($this->getPreferredLanguage());
  }

  public function getFqdn()
  {
    if($this->_domain === null)
    {
      $this->_domain = new FQDN($this->getHost());
    }
    return $this->_domain;
  }

  /**
   * Define accepted TLDs for use when determining tlds
   *
   * @param array $tlds
   * @param bool  $append
   *
   * @return self
   */
  public function defineTlds(array $tlds, $append = false)
  {
    $this->getFqdn()->defineTlds($tlds, $append);
    return $this;
  }

  /**
   * Returns a list of user defined TLDs, used for calculating domain parts
   *
   * @return array
   */
  public function getDefinedTlds()
  {
    return $this->getFqdn()->getDefinedTlds();
  }

  /**
   * http:// or https://
   *
   * @return string
   */
  public function protocol()
  {
    return $this->_cachedPart(
      'protocol',
      function () { return $this->isSecure() ? 'https://' : 'http://'; }
    );
  }

  /**
   * Sub Domain e.g. www.
   *
   * @return string|null
   */
  public function subDomain()
  {
    return $this->_cachedPart(
      'subdomain',
      function () { return $this->getFqdn()->subDomain(); }
    );
  }

  /**
   * Main domain, excluding sub domains and tlds
   *
   * @return string
   */
  public function domain()
  {
    return $this->_cachedPart(
      'domain',
      function () { return $this->getFqdn()->domain(); }
    );
  }

  /**
   * Top Level Domain
   *
   * @return string
   */
  public function tld()
  {
    return $this->_cachedPart(
      'tld',
      function () { return $this->getFqdn()->tld(); }
    );
  }

  /**
   * Port number the user is requesting on
   *
   * @return int
   */
  public function port()
  {
    return $this->_cachedPart('port', function () { return $this->getPort(); });
  }

  protected function _cachedPart($part, callable $retrieve)
  {
    if(!isset($this->_partCache[$part]))
    {
      $this->_partCache[$part] = $retrieve();
    }
    return $this->_partCache[$part];
  }

  /**
   * Returns a formatted string based on the url parts
   *
   * - %r = Port Number (no colon)
   * - %o = Port Number (with colon) - only if non standard
   * - %i = Path (leading slash)
   * - %p = Scheme with :// (Usually http:// or https://)
   * - %h = Host (Subdomain . Domain . Tld : Port [port may not be set])
   * - %d = Domain
   * - %s = Sub Domain
   * - %t = Tld
   * - %a = Scheme Host (Subdomain . Domain . Tld : Port [port may not be set])
   *
   * @param string $format
   *
   * @return string mixed
   */
  public function urlSprintf($format = "%a")
  {
    $formater = [
      "%a" => $this->protocol() . $this->getHttpHost(),
      "%r" => $this->getPort(),
      "%o" => $this->isStandardPort() ? '' : ':' . $this->getPort(),
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
   * Detect if the port is the standard based on the scheme e.g. http = 80
   *
   * @return bool
   */
  public function isStandardPort()
  {
    $scheme = $this->getScheme();
    $port = $this->getPort();

    return ('http' == $scheme && $port == 80)
    || ('https' == $scheme && $port == 443);
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

  /**
   * Pull back a specific number of parts from the URL
   *
   * @param null|int $depth depth of /
   *
   * @return null
   */
  public function path($depth = null)
  {
    if($depth !== null)
    {
      $depth++;
      $parts = explode("/", $this->getPathInfo(), $depth + 1);
      if(count($parts) > $depth)
      {
        array_pop($parts);
        return implode('/', $parts);
      }
    }
    return $this->getPathInfo();
  }

  /**
   * Retrieve a section of the path
   *
   * @param int $offset
   * @param null $limit
   *
   * @return string
   */
  public function offsetPath($offset = 0, $limit = null)
  {
    $parts = explode("/", substr($this->path(), 1));
    return '/' . implode('/', array_slice($parts, $offset, $limit));
  }

  /**
   * Create a request for the console
   *
   * @return \Cubex\Http\Request
   */
  public static function createConsoleRequest()
  {
    return self::create(
      'http://localhost',
      'GET',
      [],
      [],
      [],
      $_SERVER,
      null
    );
  }

  /**
   * Detect if the user is browsing on the private network
   *
   * @param string|null $ip IP to test
   *
   * @return bool
   */
  public function isPrivateNetwork($ip = null)
  {
    if($ip === null)
    {
      $ip = $this->getClientIp();
    }
    return starts_with_any(
      $ip,
      ['192.168.', '10.', '172.16.', '127.']
    );
  }

  /**
   * Retrieve the user agent for the request
   *
   * @param null $default
   *
   * @return mixed
   */
  public function userAgent($default = null)
  {
    return $this->server->get('HTTP_USER_AGENT', $default);
  }

  /**
   * Retrieve the referrer for the request
   *
   * @param null $default
   *
   * @return mixed
   */
  public function referrer($default = null)
  {
    return $this->server->get(
      'REFERER',
      $this->server->get('HTTP_REFERER', $default)
    );
  }
}
