<?php
namespace Cubex\Routing;

use Cubex\Context\Context;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;

class Constraint implements Condition
{
  private $_constraints = [];

  const SCHEME = 'scheme';
  const PORT = 'port';
  const PATH = 'path';
  const DOMAIN = 'domain';
  const SUBDOMAIN = 'subdomain';
  const TLD = 'tld';
  const METHOD = 'method';
  const AJAX = 'xmlhttprequest';
  const LANGUAGE = 'language';

  const TYPE_MATCH = 'match';
  const TYPE_EXACT = 'exact';
  const TYPE_START = 'start';
  const TYPE_START_CASEI = 'start.casei';
  const TYPE_REGEX = 'regex';

  const META_ROUTED_PATH = '_routing_routed_path';

  protected $_routedPath;
  protected $_extractedData = [];

  public function match(Context $context): bool
  {
    $this->_routedPath = $context->meta()->get(self::META_ROUTED_PATH, '/');
    foreach($this->_constraints as $match)
    {
      if($match[0] == self::PATH)
      {
        $match[1] = $this->_convertPathToRegex($match[1], $match[2]);
        $match[2] = self::TYPE_REGEX;
      }

      if(!$this->_matchConstraint($context, $match[0], $match[1], $match[2]))
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
    $value = $this->_matchValue($context, $matchOn);
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
        break;
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

  /**
   * @param        $on string self::*
   * @param        $with
   * @param string $type
   *
   * @return $this
   */
  final public function add($on, $with, $type = self::TYPE_MATCH)
  {
    $this->_constraints[] = [$on, $with, $type];
    return $this;
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
        $path .= '$';
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
