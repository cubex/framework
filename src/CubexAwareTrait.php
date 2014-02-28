<?php
namespace Cubex;

trait CubexAwareTrait
{
  /**
   * @var Cubex
   */
  protected $_cubex;

  /**
   * Set the cubex application
   *
   * @param Cubex $app
   */
  public function setCubex(Cubex $app)
  {
    $this->_cubex = $app;
  }

  /**
   * Retrieve the cubex application
   *
   * @return Cubex
   *
   * @throws \RuntimeException
   */
  public function getCubex()
  {
    if(!$this->isCubexAvailable())
    {
      throw new \RuntimeException(
        "The cubex application has not been set",
        404
      );
    }
    return $this->_cubex;
  }

  public function isCubexAvailable()
  {
    return !($this->_cubex === null || !($this->_cubex instanceof Cubex));
  }
}
