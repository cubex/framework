<?php
namespace Cubex\View;

use Illuminate\Contracts\Support\Renderable as RenderableInterface;

class Renderable implements RenderableInterface
{
  /**
   * @var string
   */
  protected $_data;

  /**
   * Convert the provided data into a renderable string
   *
   * @param $data
   */
  public function __construct($data)
  {
    $this->_data = $data;
  }

  public function __toString()
  {
    return $this->render();
  }

  public function render()
  {
    return (string)$this->_data;
  }
}
