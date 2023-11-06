<?php

namespace Cubex\ViewModel;

use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\SafeHtml\SafeHtml;
use Packaged\Ui\TemplateLoaderTrait;

class TemplatedViewModel extends ViewModel
{
  use TemplateLoaderTrait;

  public function createView(string $viewClass = null)
  {
    if($viewClass === null && empty($this->_defaultView))
    {
      return $this;
    }

    return parent::createView($viewClass);
  }

  protected function _render(): ?ISafeHtmlProducer
  {
    return new SafeHtml($this->_renderTemplate());
  }
}