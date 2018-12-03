<?php
namespace Cubex\Ui;

use Composer\Autoload\ClassLoader;
use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Exception;
use Packaged\Ui\Element;

class UiElement extends Element implements ContextAware
{
  use ContextAwareTrait;

  protected function getTemplateFilePath()
  {
    if($this->_templateFilePath === null && $this->hasContext())
    {
      $ctx = $this->getContext();
      if($ctx)
      {
        try
        {
          $loader = $ctx->getCubex()->retrieve(ClassLoader::class);
          if($loader instanceof ClassLoader)
          {
            $filePath = $loader->findFile(static::class);
            if($filePath)
            {
              $this->_templateFilePath = substr($filePath, 0, -3) . 'phtml';
            }
          }
        }
        catch(Exception $e)
        {
        }
      }
    }

    if($this->_templateFilePath === null)
    {
      return parent::getTemplateFilePath();
    }

    return $this->_templateFilePath;
  }
}
