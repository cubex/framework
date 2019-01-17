<?php
namespace Cubex\Tests\Supporting\Ui;

use Packaged\SafeHtml\ISafeHtmlProducer;
use Packaged\SafeHtml\SafeHtml;

class TestSafeHtmlProducer implements ISafeHtmlProducer
{
  public function produceSafeHTML()
  {
    return new SafeHtml('<b>Test</b>');
  }

}
