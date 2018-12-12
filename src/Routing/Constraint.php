<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;

class Constraint implements Condition
{
  private $_constraints = [];

  const PROTOCOL = 'protocol';
  const SCHEME = 'scheme';
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

  const META_ROUTED_PATH = '_routing_routed_path';

  protected $_routedPath;

  public function match(Context $context): bool
  {
    $this->_routedPath = $context->meta()->get(self::META_ROUTED_PATH, '/');
    foreach($this->_constraints as $match)
    {
      if(!$this->_matchConstraint($context, $match[0], $match[1], $match[2]))
      {
        return false;
      }
    }
    $context->meta()->set(self::META_ROUTED_PATH, $this->_routedPath);
    return true;
  }

  protected function _matchValue(Context $context, $on)
  {
    switch($on)
    {
      case self::PATH;
        return $context->getRequest()->path();
      case self::SUBDOMAIN;
        return $context->getRequest()->subDomain();
      case self::DOMAIN;
        return $context->getRequest()->domain();
      case self::TLD;
        return $context->getRequest()->tld();
      case self::PROTOCOL;
        return $context->getRequest()->protocol();
      case self::SCHEME;
        return $context->getRequest()->getScheme();
      case self::PORT;
        return $context->getRequest()->port();
      case self::METHOD;
        return $context->getRequest()->getRealMethod();
      case self::AJAX;
        return $context->getRequest()->isXmlHttpRequest();
      default:
        return null;
    }
  }

  protected function _matchConstraint(Context $context, $matchOn, $matchWith, $matchType)
  {
    if($matchOn == self::PATH && $matchWith[0] !== '/')
    {
      $matchWith = Path::build($this->_routedPath, $matchWith);
    }
    $value = $this->_matchValue($context, $matchOn);
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

    if($matchOn == self::PATH && $matchWith[0] == '/')
    {
      $this->_routedPath = $matchWith;
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

  public static function scheme($protocol)
  {
    $cond = new static();
    $cond->add(self::SCHEME, $protocol);
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
