<?php

namespace Cubex\Tests\Ui;

use Cubex\Context\Context;
use Cubex\Cubex;
use Cubex\Tests\Supporting\Ui\TestElement\TestFakeLoader;
use Cubex\Tests\Supporting\Ui\TestElement\TestUiElement;
use PHPUnit\Framework\TestCase;

class UiElementTest extends TestCase
{
  /**
   * @throws \Throwable
   */
  public function testTemplate()
  {
    $element = new TestUiElement();
    $this->_assertTemplate($element);
  }

  /**
   * @throws \Throwable
   */
  public function testTemplateWithContext()
  {
    $ctx = new Context();

    $element = new TestUiElement();
    $element->setContext($ctx);
    $this->_assertTemplate($element);

    $cubex = new Cubex(__DIR__, null, false);
    $ctx->setCubex($cubex);

    $element = new TestUiElement();
    $element->setContext($ctx);
    $this->_assertTemplate($element);

    $loader = new TestFakeLoader();
    $cubex = new Cubex(__DIR__, $loader, false);
    $ctx->setCubex($cubex);

    $element = new TestUiElement();
    $element->setContext($ctx);
    $this->_assertTemplate($element, 'h2');
  }

  /**
   * @param TestUiElement $element
   *
   * @param string        $tag
   *
   * @throws \Throwable
   */
  protected function _assertTemplate(TestUiElement $element, $tag = 'p')
  {
    $element->setContent('Test');
    $this->assertStringStartsWith("<$tag>Test</$tag>", $element->render());
  }
}
