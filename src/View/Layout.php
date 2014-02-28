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
   * @param RenderableInterface $renderable
   * @param                     $sectionName
   *
   * @return $this
   */
  public function insert(RenderableInterface $renderable, $sectionName)
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
  }
}
