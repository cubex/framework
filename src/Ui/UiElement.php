<?php
namespace Cubex\Ui;

use Composer\Autoload\ClassLoader;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Packaged\Ui\Element;

class UiElement extends Element implements ContextAware
{
  use ContextAwareTrait;

  protected function _getTemplateFilePath()
  {
    //Set the classLoader on Element if we have it available in DI
    if($this->_templateFilePath === null && $this->_classLoader === null
      && $this->hasContext() && $this->getContext()->hasCubex())
    {
      try
      {
        $loader = $this->getContext()->getCubex()->retrieve(ClassLoader::class);
        if($loader instanceof ClassLoader)
        {
          $this->_setClassLoader($loader);
        }
      }
      catch(\Exception $e)
      {
      }
    }

    return parent::_getTemplateFilePath();
  }
}
