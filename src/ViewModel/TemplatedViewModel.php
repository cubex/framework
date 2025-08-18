<?php
namespace Cubex\ViewModel;

use Packaged\Ui\TemplateLoaderTrait;

class TemplatedViewModel extends ViewModel implements View
{
  use TemplateLoaderTrait;

  protected $_variants = [];

  public function setModel(Model $data)
  {
    // To not use external models
    return $this;
  }

  public function render(): string
  {
    return $this->_renderTemplate();
  }

  public function addVariant(string $variant, string $extension = 'phtml'): self
  {
    array_unshift($this->_variants, join('.', [$variant, $extension]));
    return $this;
  }

  public function clearVariants(): self
  {
    $this->_variants = [];
    return $this;
  }

  public function getVariants(): array
  {
    return $this->_variants;
  }

  protected function _attemptTemplateExtensions()
  {
    return array_filter(array_merge($this->_variants, ['phtml']));
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
