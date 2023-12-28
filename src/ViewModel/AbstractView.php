<?php
namespace Cubex\ViewModel;

use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Packaged\SafeHtml\ISafeHtmlProducer;

abstract class AbstractView implements View, ContextAware
{
  use ContextAwareTrait;

  protected ?Model $_model;

  public function setModel(Model $data) { $this->_model = $data; }

  protected function _render(): ?ISafeHtmlProducer
  {
    return null;
  }

  public function render(): string
  {
    $rendered = $this->_render();
    return $rendered ? $rendered->produceSafeHTML()->getContent() : '';
  }
}
