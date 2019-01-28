<?php
namespace Cubex\Routing;

class HttpConstraint
{
  public static function any()
  {
    return new Constraint();
  }

  public static function path($path, $type = Constraint::TYPE_MATCH)
  {
    $cond = new Constraint();
    $cond->add(Constraint::PATH, $path, $type);
    return $cond;
  }

  public static function scheme($protocol)
  {
    $cond = new Constraint();
    $cond->add(Constraint::SCHEME, $protocol);
    return $cond;
  }

  public static function port($port)
  {
    $cond = new Constraint();
    $cond->add(Constraint::PORT, $port);
    return $cond;
  }

  public static function method($method)
  {
    $cond = new Constraint();
    $cond->add(Constraint::METHOD, $method);
    return $cond;
  }

  public static function ajax()
  {
    $cond = new Constraint();
    $cond->add(Constraint::AJAX, true);
    return $cond;
  }

  public static function domain($domain, $tld = null)
  {
    $cond = new Constraint();
    $cond->add(Constraint::DOMAIN, $domain);
    if($tld !== null)
    {
      $cond->add(Constraint::TLD, $tld);
    }
    return $cond;
  }

  public static function subDomain($subdomain, $domain = null, $tld = null)
  {
    $cond = new Constraint();
    $cond->add(Constraint::SUBDOMAIN, $subdomain);
    if($domain !== null)
    {
      $cond->add(Constraint::DOMAIN, $domain);
    }
    if($tld !== null)
    {
      $cond->add(Constraint::TLD, $tld);
    }
    return $cond;
  }
}
