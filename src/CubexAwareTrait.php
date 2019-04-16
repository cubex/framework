<?php
namespace Cubex;

trait CubexAwareTrait
{
  private $_cubex;

  /**
   * @return Cubex|null
   */
  public function getCubex()
  {
    return $this->_cubex;
  }

  /**
   * @param Cubex $cubex
   *
   * @return static
   */
  public function setCubex(Cubex $cubex)
  {
    $this->_cubex = $cubex;
    return $this;
  }

  /**
   * @return $this
   */
  protected function _clearCubex()
  {
    $this->_cubex = null;
    return $this;
  }

  public function hasCubex(): bool
  {
    return $this->_cubex instanceof Cubex;
  }
}
