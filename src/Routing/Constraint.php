<?php
namespace Cubex\Routing;

use Packaged\Helpers\Strings;
use Packaged\Http\Request;

class Constraint implements Condition
{
  private $_constraints = [];

  const PROTOCOL = 'protocol';
  const PORT = 'port';
  const PATH = 'path';
  const DOMAIN = 'domain';
  const SUBDOMAIN = 'subdomain';
  const TLD = 'tld';
  const METHOD = 'method';
  const AJAX = 'xmlhttprequest';
  const LANGUAGE = 'language';

  const TYPE_EXACT = 'exact';
  const TYPE_START = 'start';
  const TYPE_START_CASEI = 'start.casei';

  public function match(Request $request): bool
  {
    foreach($this->_constraints as $match)
    {
      if(!$this->_matchConstraint($request, $match[0], $match[1], $match[2]))
      {
        return false;
      }
    }
    return true;
  }

  protected function _matchValue(Request $request, $on)
  {
    switch($on)
    {
      case self::PATH;
        return $request->path();
      case self::SUBDOMAIN;
        return $request->subDomain();
      case self::DOMAIN;
        return $request->domain();
      case self::TLD;
        return $request->tld();
      case self::PROTOCOL;
        return $request->protocol();
      case self::PORT;
        return $request->port();
      case self::METHOD;
        return $request->getRealMethod();
      case self::AJAX;
        return $request->isXmlHttpRequest();
      default:
        return null;
    }
  }

  protected function _matchConstraint(Request $request, $matchOn, $matchWith, $matchType)
  {
    $value = $this->_matchValue($request, $matchOn);
    switch($matchType)
    {
      case self::TYPE_START:
        if(!Strings::startsWith($value, $matchWith))
        {
          return false;
        }
        break;
      case self::TYPE_START_CASEI:
        if(!Strings::startsWith($value, $matchWith, false))
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
        break;
    }
    return true;
  }

  /**
   * @param        $on string self::*
   * @param        $with
   * @param string $type
   *
   * @return $this
   */
  final public function add($on, $with, $type = self::TYPE_EXACT)
  {
    $this->_constraints[] = [$on, $with, $type];
    return $this;
  }

  public static function any()
  {
    return new static();
  }

  public static function path($path, $type = self::TYPE_START_CASEI)
  {
    $cond = new static();
    $cond->add(self::PATH, $path, $type);
    return $cond;
  }

  public static function protocol($protocol)
  {
    $cond = new static();
    $cond->add(self::PROTOCOL, $protocol);
    return $cond;
  }

  public static function port($port)
  {
    $cond = new static();
    $cond->add(self::PORT, $port);
    return $cond;
  }

  public static function method($method)
  {
    $cond = new static();
    $cond->add(self::METHOD, $method);
    return $cond;
  }

  public static function ajax()
  {
    $cond = new static();
    $cond->add(self::AJAX, true);
    return $cond;
  }

  public static function domain($domain, $tld = null)
  {
    $cond = new static();
    $cond->add(self::DOMAIN, $domain);
    if($tld !== null)
    {
      $cond->add(self::TLD, $tld);
    }
    return $cond;
  }

  public static function subDomain($subdomain, $domain = null, $tld = null)
  {
    $cond = new static();
    $cond->add(self::SUBDOMAIN, $subdomain);
    if($domain !== null)
    {
      $cond->add(self::DOMAIN, $domain);
    }
    if($tld !== null)
    {
      $cond->add(self::TLD, $tld);
    }
    return $cond;
  }
}
