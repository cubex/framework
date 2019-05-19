<?php
namespace Cubex\Routing;

use Packaged\Context\Context;
use Exception;
use InvalidArgumentException;
use Packaged\Helpers\Strings;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function rtrim;
use function strpos;

class RequestConstraint implements Condition, RouteCompleter
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

  public static function i()
  {
    return new static();
  }

  public function path($path, $type = self::TYPE_MATCH)
  {
    return $this->_add(self::PATH, $path, $type);
  }

  protected function _add($on, $with, $type = self::TYPE_MATCH)
  {
    $this->_constraints[] = [$on, $with, $type];
    return $this;
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
    return true;
  }

  protected function _convertPathToRegex($path, $type)
  {
    if(empty($path))
    {
      $path = $this->_routedPath;
    }
    else if($path[0] !== '/')
    {
      $path = rtrim($this->_routedPath, '/') . '/' . $path;
    }

    if(strpos($path, '{') !== false)
    {
      $idPat = "([a-zA-Z_][a-zA-Z0-9_\-]*)";
      $path = preg_replace(
        [
          "/{" . "$idPat\@alphanum}/",
          "/{" . "$idPat\@alnum}/",
          "/{" . "$idPat\@alpha}/",
          "/{" . "$idPat\@all}/",
          "/{" . "$idPat\@num}/",
          "/{" . "$idPat}/",
        ],
        [
          "(?P<$1>\w+)",
          "(?P<$1>\w+)",
          "(?P<$1>[a-zA-Z]+)",
          "(?P<$1>.+)",
          "(?P<$1>\d+)",
          "(?P<$1>[^\/]+)",
        ],
        $path
      );
    }

    $flags = 'u';
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
        try
        {
          if(!preg_match($matchWith, $value, $matches))
          {
            return false;
          }
        }
        catch(Exception $e)
        {
          throw new InvalidArgumentException('Invalid regex passed to path ' . $matchWith, 400, $e);
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

    return true;
  }

  protected function _matchValue(Context $context, $on, $matchWith)
  {
    switch($on)
    {
      case self::PATH;
        return $context->request()->path();
      case self::SUBDOMAIN;
        return $context->request()->subDomain();
      case self::ROOT_DOMAIN;
        return $context->request()->urlSprintf('%d.%t');
      case self::DOMAIN;
        return $context->request()->domain();
      case self::TLD;
        return $context->request()->tld();
      case self::SCHEME;
        return $context->request()->getScheme();
      case self::PORT;
        return $context->request()->port();
      case self::METHOD;
        return $context->request()->getRealMethod();
      case self::AJAX;
        return $context->request()->isXmlHttpRequest();
      case self::QUERY_KEY;
        return $context->request()->query->has($matchWith) ? $matchWith : null;
      case self::QUERY_VALUE;
        return $context->request()->query->get($matchWith[0]);
      case self::HOSTNAME;
        return $context->request()->getHost();
    }
    // @codeCoverageIgnoreStart
    return null;
    // @codeCoverageIgnoreEnd
  }

  public function complete(Context $context)
  {
    $context->meta()->set(self::META_ROUTED_PATH, $this->_routedPath);
    if(!empty($this->_extractedData))
    {
      $context->routeData()->add($this->_extractedData);
    }
  }
}
