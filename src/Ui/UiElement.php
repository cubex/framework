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
    if($this->_templateFilePath === null && $this->hasContext())
    {

      if($this->hasContext() && $this->getContext()->hasCubex())
      {
        try
        {
          $loader = $this->getContext()->getCubex()->retrieve(ClassLoader::class);
          if($loader instanceof ClassLoader)
          {
            $filePath = $loader->findFile(static::class);
            if($filePath)
            {
              $this->_templateFilePath = realpath(substr($filePath, 0, -3) . 'phtml');
            }
          }
        }
        catch(\Throwable $e)
        {
          //If we cant get a file path, allow the parent method to be called
        }
      }
    }

    return $this->_templateFilePath ?? parent::_getTemplateFilePath();
  }
}
