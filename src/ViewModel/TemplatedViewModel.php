<?php
namespace Cubex\ViewModel;

use Packaged\Ui\TemplateLoaderTrait;

class TemplatedViewModel extends ViewModel implements View
{
  use TemplateLoaderTrait;

  public function setModel(Model $data)
  {
    // To not use external models
    return $this;
  }

  public function render(): string
  {
    return $this->_renderTemplate();
  }

  public function createView(string $overrideViewClass = null): ?View
  {
    if($overrideViewClass === null && empty($this->_defaultView))
    {
      return $this;
    }

    return parent::createView($overrideViewClass);
  }
}