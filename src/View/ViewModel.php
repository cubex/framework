<?php
namespace Cubex\View;

use Cubex\Cubex;
use Cubex\CubexAwareTrait;
use Cubex\I18n\TranslationTrait;
use Cubex\ICubexAware;
use Illuminate\Support\Contracts\RenderableInterface;
use Packaged\Helpers\Path;

abstract class ViewModel implements RenderableInterface, ICubexAware
{
  use CubexAwareTrait;
  use TranslationTrait;

  protected $_templateDirName = 'Templates';

  /**
   * @var string Directory containing all
   */
  protected $_templateDir;

  /**
   * @var string Template file (without extension) to load
   */
  protected $_templateFile;

  /**
   * @var int Depth of the view
   */
  protected $_viewDepth = 0;

  protected $_callingClass;

  /**
   * Set the base directory for templates to be read from
   *
   * @param $directory
   *
   * @return $this
   */
  public function setTemplateDir($directory)
  {
    $this->_templateDir = $directory;
    return $this;
  }

  /**
   * Get the base directory templates are stored
   *
   * @return null|string
   */
  public function getTemplateDir()
  {
    if($this->_templateDir === null)
    {
      $this->_calculateTemplateDefaults();
    }
    return $this->_templateDir;
  }

  /**
   * Set the template file manually for this view
   *
   * @param $filename
   *
   * @return $this
   */
  public function setTemplateFile($filename)
  {
    $this->_templateFile = $filename;
    return $this;
  }

  /**
   * Get the template file (excluding extension) relative to the template dir
   *
   * @return null|string
   */
  public function getTemplateFile()
  {
    if($this->_templateFile === null)
    {
      $this->_calculateTemplateDefaults();
    }
    return $this->_templateFile;
  }

  /**
   * Calculate the base template directory
   */
  protected function _calculateTemplateDefaults()
  {
    if($this->_callingClass === null)
    {
      $this->_callingClass = get_called_class();
    }
    else if(!is_scalar($this->_callingClass))
    {
      $this->_callingClass = get_class($this->_callingClass);
    }

    $parts   = explode('\\', $this->_callingClass);
    $nesting = [];

    if(!empty($parts) && in_array('Views', $parts))
    {
      $parts = array_reverse($parts);
      foreach($parts as $part)
      {
        //Looks for the views directory
        if($part === 'Views')
        {
          break;
        }
        $nesting[] = $part;
        $this->_viewDepth++;
      }
    }
    else
    {
      $nesting[]        = class_basename($this->_callingClass);
      $this->_viewDepth = 1;
    }

    $this->_templateDir = dirname(
      (new \ReflectionClass($this->_callingClass))->getFileName()
    );

    for($i = 0; $i < $this->_viewDepth; $i++)
    {
      $this->_templateDir = dirname($this->_templateDir);
    }

    $this->_templateDir = Path::build(
      $this->_templateDir,
      $this->_templateDirName
    );

    if($this->_templateFile === null)
    {
      $this->_templateFile = Path::buildCustom(
        DIRECTORY_SEPARATOR,
        array_reverse($nesting)
      );
    }
  }

  /**
   * Get the full path to the template file for this viewmodel
   *
   * @param string $extension
   *
   * @return string
   */
  public function getTemplatePath($extension = '.phtml')
  {
    return Path::build(
      $this->getTemplateDir(),
      $this->getTemplateFile()
    ) . $extension;
  }

  /**
   * Convert the view model to a string by calling render
   *
   * @return string
   */
  public function __toString()
  {
    try
    {
      return $this->render();
    }
    catch(\Exception $e)
    {
      return Cubex::exceptionAsString($e);
    }
  }
}
