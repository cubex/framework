<?php
namespace Cubex;

/**
 * Simplify passing of Cubex around
 */
interface ICubexAware
{
  /**
   * Set the cubex application
   *
   * @param Cubex $app
   *
   */
  public function setCubex(Cubex $app);

  /**
   * Retrieve the cubex application
   *
   * @return Cubex
   */
  public function getCubex();
}
