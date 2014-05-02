<?php
namespace Cubex\View;

use Illuminate\Support\Contracts\RenderableInterface;

/**
 * Convert a data array to a string with a template
 */
class ArrayTemplate extends \ArrayIterator implements RenderableInterface
{
  protected $_template;
  protected $_glue;

  /**
   * Create a new array template with glue, template & items
   *
   * @param string $template
   * @param string $glue
   * @param array  $items
   *
   * @return static
   */
  public static function create(
    $template = '', $glue = '', array $items = array()
  )
  {
    $object = new static;
    $object->setTemplate($template);
    $object->setGlue($glue);
    foreach($items as $item)
    {
      $object->append($item);
    }
    return $object;
  }

  /**
   * Set the sprintf string to perform on each item
   *
   * @param string $template
   *
   * @return $this
   */
  public function setTemplate($template = '')
  {
    $this->_template = $template;
    return $this;
  }

  /**
   * Set the glue to sit between each item when rendering multiple items
   *
   * @param string $glue
   *
   * @return $this
   */
  public function setGlue($glue = '')
  {
    $this->_glue = $glue;
    return $this;
  }

  /**
   * Render a single item against the template
   *
   * @param array $values
   *
   * @return string
   */
  public function renderItem(array $values)
  {
    return vsprintf($this->_template, $values);
  }

  /**
   * Render all items in the array with the template & glue
   *
   * @return string
   */
  public function render()
  {
    $return = '';
    foreach($this->getArrayCopy() as $i => $values)
    {
      if($i !== 0)
      {
        $return .= $this->_glue;
      }
      $return .= vsprintf($this->_template, $values);
    }
    return $return;
  }
}
