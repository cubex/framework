<?php
namespace Cubex\ViewModel;

class JsonView extends AbstractView
{
  protected int $_flags = JSON_PRETTY_PRINT;

  /**
   * @param int $flags
   *
   *                   Bitmask consisting of <b>JSON_HEX_QUOT</b>,
   *                   <b>JSON_HEX_TAG</b>,
   *                   <b>JSON_HEX_AMP</b>,
   *                   <b>JSON_HEX_APOS</b>,
   *                   <b>JSON_NUMERIC_CHECK</b>,
   *                   <b>JSON_PRETTY_PRINT</b>,
   *                   <b>JSON_UNESCAPED_SLASHES</b>,
   *                   <b>JSON_FORCE_OBJECT</b>,
   *                   <b>JSON_UNESCAPED_UNICODE</b>.
   *                   <b>JSON_THROW_ON_ERROR</b> The behaviour of these
   *                   constants is described on
   *                   the JSON constants page.
   *
   * @return $this
   */
  public function setFlags(int $flags = 0)
  {
    $this->_flags = $flags;
    return $this;
  }

  public function render(): string
  {
    return json_encode($this->_model, $this->_flags);
  }
}
