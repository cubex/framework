<?php
namespace Cubex\View;

use Cubex\Kernel\CubexKernel;
use Illuminate\Support\Contracts\RenderableInterface;

class Layout extends TemplatedViewModel
{
  /**
   * Directory for layouts to be included from
   *
   * @var string
   */
  protected $_templateDirName = 'Layouts';

  /**
   * @var RenderableInterface[]
   */
  protected $_sections = [];

  /**
   * Custom data items
   *
   * @var array
   */
  protected $_data;

  /**
   * Create a new layout for rendering
   *
   * @param CubexKernel $base
   * @param string      $layoutName
   */
  public function __construct(CubexKernel $base, $layoutName = 'Default')
  {
    $this->_callingClass = $base;
    $this->_templateFile = $layoutName;
  }

  /**
   * Check to see if a section has been added to the layout
   *
   * @param $sectionName
   *
   * @return bool
   */
  public function exists($sectionName)
  {
    return isset($this->_sections[$sectionName]);
  }

  /**
   * Add a new section to the layout
   *
   * @param                     $sectionName
   * @param RenderableInterface $renderable
   *
   * @return $this
   */
  public function insert($sectionName, RenderableInterface $renderable)
  {
    $this->_sections[$sectionName] = $renderable;
    return $this;
  }

  /**
   * Remove a section from the layout
   *
   * @param $sectionName
   *
   * @return $this
   */
  public function remove($sectionName)
  {
    unset($this->_sections[$sectionName]);
    return $this;
  }

  /**
   * Retrieve the renderable object bound to this section
   *
   * @param $sectionName
   *
   * @return RenderableInterface
   * @throws \Exception
   */
  public function get($sectionName)
  {
    if(isset($this->_sections[$sectionName]))
    {
      return $this->_sections[$sectionName];
    }
    throw new \Exception("$sectionName has not yet been bound to this layout");
  }

  /**
   * Retrieve the value of an item
   *
   * @param      $key
   * @param null $default
   *
   * @return null
   */
  public function getData($key, $default = null)
  {
    return isset($this->_data[$key]) ? $this->_data[$key] : $default;
  }

  /**
   * Set a custom data item
   *
   * @param $key
   * @param $value
   *
   * @return $this
   */
  public function setData($key, $value)
  {
    $this->_data[$key] = $value;
    return $this;
  }

  /**
   * Allow for sections to be rendered by calling them by name
   *
   * e.g. $this->sectionName();
   *
   * @param $method
   * @param $args
   *
   * @return string
   */
  public function __call($method, $args)
  {
    if(isset($this->_sections[$method]))
    {
      return $this->_sections[$method]->render($args);
    }
    return null;
  }

  /**
   * Set the full class name to use when looking up the layout files on the fs.
   *
   * @param $class string
   *
   * @return $this
   */
  public function setCallingClass($class)
  {
    $this->_callingClass = (string)$class;
    return $this;
  }
}
