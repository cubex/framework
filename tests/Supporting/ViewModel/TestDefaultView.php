<?php

namespace Cubex\Tests\Supporting\ViewModel;

use Cubex\ViewModel\AbstractView;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\SafeHtml\SafeHtml;

class TestDefaultView extends AbstractView
{
  protected function _render(): ?ISafeHtmlProducer
  {
    return new SafeHtml('<h1>Default View</h1>');
  }
}
