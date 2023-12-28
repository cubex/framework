<?php

namespace Cubex\Tests\Supporting\ViewModel;

use Cubex\ViewModel\AbstractView;
use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\SafeHtml\SafeHtml;

/**
 * @property TestViewModel $_model
 */
class TestView extends AbstractView
{
  protected function _render(): ?ISafeHtmlProducer
  {
    $string = $this->_model->test;
    return new SafeHtml("<h1>$string View</h1>");
  }
}
