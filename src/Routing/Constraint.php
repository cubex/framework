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
    if(!empty($this->_constraints))
    {
      $matched = true;
      foreach($this->_constraints as $match)
      {
        [$matchOn, $matchWith, $matchType] = $match;
        switch($matchOn)
        {
          case self::PATH;
            $value = $request->path();
            break;
          case self::SUBDOMAIN;
            $value = $request->subDomain();
            break;
          case self::DOMAIN;
            $value = $request->domain();
            break;
          case self::TLD;
            $value = $request->tld();
            break;
          case self::PROTOCOL;
            $value = $request->protocol();
            break;
          case self::PORT;
            $value = $request->port();
            break;
          case self::METHOD;
            $value = $request->getRealMethod();
            break;
          case self::AJAX;
            $value = $request->isXmlHttpRequest();
            break;
          default:
            $value = null;
            break;
        }

        switch($matchType)
        {
          case self::TYPE_START:
            if(!Strings::startsWith($value, $matchWith))
            {
              $matched = false;
            }
            break;
          case self::TYPE_START_CASEI:
            if(!Strings::startsWith($value, $matchWith, false))
            {
              $matched = false;
            }
            break;
          case self::TYPE_EXACT:
          default:
            if($value !== $matchWith)
            {
              $matched = false;
            }
            break;
        }
      }
      return $matched;
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
