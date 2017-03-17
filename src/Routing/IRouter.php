<?php
namespace Cubex\Routing;

use Cubex\ICubexAware;

interface IRouter extends ICubexAware
{
  /**
   * Set the object you wish to handle routing for
   *
   * @param IRoutable $subject
   *
   * @return $this
   */
  public function setSubject(IRoutable $subject);

  /**
   * Process the url against the subjects routes
   *
   * @param $url
   *
   * @return IRoute
   * @throws \RuntimeException When the subject has not been set
   * @throws \Exception When no route can be found
   */
  public function process($url);

  /**
   * Get the matched route
   *
   * @return string
   */
  public function getRoute();
}
