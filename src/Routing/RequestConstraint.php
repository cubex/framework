<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;

class RequestConstraint implements Condition
{
  const SCHEME = 'scheme';
  const PORT = 'port';
  const PATH = 'path';
  const DOMAIN = 'domain';
  const SUBDOMAIN = 'subdomain';
  const TLD = 'tld';
  const METHOD = 'method';
  const AJAX = 'xmlhttprequest';
  const LANGUAGE = 'language';
  const ROOT_DOMAIN = 'rootdomain';
  const HOSTNAME = 'hostname';
  const QUERY_KEY = 'querykey';
  const QUERY_VALUE = 'queryvalue';

  const TYPE_MATCH = 'match';
  const TYPE_EXACT = 'exact';
  const TYPE_START = 'start';
  const TYPE_START_CASEI = 'start.casei';
  const TYPE_REGEX = 'regex';

  const META_ROUTED_PATH = '_routing_routed_path';

  protected $_routedPath;
  protected $_extractedData = [];

  protected $_constraints = [];

  protected function _add($on, $with, $type = self::TYPE_MATCH)
  {
    $this->_constraints[] = [$on, $with, $type];
    return $this;
  }

  public static function i()
  {
    return new static();
  }

  public function path($path, $type = self::TYPE_MATCH)
  {
    return $this->_add(self::PATH, $path, $type);
  }

  public function scheme($protocol, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::SCHEME, $protocol, $matchType);
  }

  public function port($port, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::PORT, $port, $matchType);
  }

  public function method($method, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::METHOD, $method, $matchType);
  }

  public function ajax()
  {
    return $this->_add(self::AJAX, true);
  }

  public function domain($domain, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::DOMAIN, $domain, $matchType);
  }

  public function tld($tld, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::TLD, $tld, $matchType);
  }

  public function rootDomain($rootDomain, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::ROOT_DOMAIN, $rootDomain, $matchType);
  }

  public function subDomain($subdomain, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::SUBDOMAIN, $subdomain, $matchType);
  }

  public function hostname($hostname, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::HOSTNAME, $hostname, $matchType);
  }

  public function hasQueryKey($key, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::QUERY_KEY, $key, $matchType);
  }

  public function hasQueryValue($key, $value, $matchType = self::TYPE_MATCH)
  {
    return $this->_add(self::QUERY_VALUE, [$key, $value], $matchType);
  }

  public function match(Context $context): bool
  {
    $this->_routedPath = $context->meta()->get(self::META_ROUTED_PATH, '/');
    foreach($this->_constraints as [$matchOn, $matchWith, $matchType])
    {
      if($matchOn == self::PATH)
      {
        if($matchWith === '/')
        {
          $matchType = self::TYPE_START;
        }
        else
        {
          $matchWith = $this->_convertPathToRegex($matchWith, $matchType);
          $matchType = self::TYPE_REGEX;
        }
      }

      if(!$this->_matchConstraint($context, $matchOn, $matchWith, $matchType))
      {
        return false;
      }
    }
    $context->meta()->set(self::META_ROUTED_PATH, $this->_routedPath);
    if(!empty($this->_extractedData))
    {
      $context->routeData()->add($this->_extractedData);
    }
    return true;
  }

  protected function _matchValue(Context $context, $on, $matchWith)
  {
    switch($on)
    {
      case self::PATH;
        return $context->getRequest()->path();
      case self::SUBDOMAIN;
        return $context->getRequest()->subDomain();
      case self::ROOT_DOMAIN;
        return $context->getRequest()->urlSprintf('%d.%t');
      case self::DOMAIN;
        return $context->getRequest()->domain();
      case self::TLD;
        return $context->getRequest()->tld();
      case self::SCHEME;
        return $context->getRequest()->getScheme();
      case self::PORT;
        return $context->getRequest()->port();
      case self::METHOD;
        return $context->getRequest()->getRealMethod();
      case self::AJAX;
        return $context->getRequest()->isXmlHttpRequest();
      case self::QUERY_KEY;
        return $context->getRequest()->query->has($matchWith) ? $matchWith : null;
      case self::QUERY_VALUE;
        return $context->getRequest()->query->get($matchWith[0]);
      case self::HOSTNAME;
        return $context->getRequest()->getHost();
    }
    // @codeCoverageIgnoreStart
    return null;
    // @codeCoverageIgnoreEnd
  }

  protected function _matchConstraint(Context $context, $matchOn, $matchWith, $matchType)
  {
    $value = $this->_matchValue($context, $matchOn, $matchWith);
    if($matchOn == self::QUERY_VALUE)
    {
      $matchWith = $matchWith[1];
    }
    $matches = [];
    switch($matchType)
    {
      case self::TYPE_REGEX:
        if(!preg_match($matchWith, $value, $matches))
        {
          return false;
        }
        break;
      case self::TYPE_START:
      case self::TYPE_START_CASEI:
        if(!Strings::startsWith($value, $matchWith, $matchType == self::TYPE_START))
        {
          return false;
        }
        break;
      case self::TYPE_MATCH:
        if($value != $matchWith)
        {
          return false;
        }
        break;
      case self::TYPE_EXACT:
      default:
        if($value !== $matchWith)
        {
          return false;
        }
    }

    $this->_processMatches($matchOn, $matches);

    return true;
  }

  /**
   * @param $matchOn
   * @param $matches
   */
  protected function _processMatches($matchOn, $matches): void
  {
    if($matchOn == self::PATH && !empty($matches[0]))
    {
      $this->_routedPath = $matches[0];
      foreach($matches as $k => $v)
      {
        if(!is_numeric($k))
        {
          $this->_extractedData[$k] = $v;
        }
      }
    }
  }

  protected function _convertPathToRegex($path, $type)
  {
    if(empty($path) || $path[0] !== '/')
    {
      $path = Path::url($this->_routedPath, $path);
    }

    $flags = 'u';
    if(strstr($path, '{'))
    {
      $idPat = "(_?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
      $repl = [
        "/{" . "$idPat\@alphanum}/" => "(?P<$1>\w+)",
        "/{" . "$idPat\@alnum}/"    => "(?P<$1>\w+)",
        "/{" . "$idPat\@alpha}/"    => "(?P<$1>[a-zA-Z]+)",
        "/{" . "$idPat\@all}/"      => "(?P<$1>.+)",
        "/{" . "$idPat\@num}/"      => "(?P<$1>\d+)",
        "/{" . "$idPat}/"           => "(?P<$1>[^\/]+)",
      ];
      $path = preg_replace(array_keys($repl), array_values($repl), $path);
    }

    $path = '#^' . $path;
    switch($type)
    {
      case self::TYPE_START:
        break;
      case self::TYPE_EXACT:
        $path .= '$';
        break;
      case self::TYPE_MATCH:
        // path should always match full parts, so check ends with slash or end of string
        $path .= '(?=/|$)';
        $flags .= 'i';
        break;
      case self::TYPE_START_CASEI:
      default:
        $flags .= 'i';
        break;
    }

    return $path . '#' . $flags;
  }
}
