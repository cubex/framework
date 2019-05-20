<?php
namespace Cubex;

interface CubexAware
{
  /**
   * @return Cubex|null
   */
  public function getCubex(): ?Cubex;

  /**
   * @param Cubex $cubex
   *
   * @return static
   */
  public function setCubex(Cubex $cubex);

  public function hasCubex(): bool;
}
