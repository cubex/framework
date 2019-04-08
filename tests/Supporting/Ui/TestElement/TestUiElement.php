<?php
namespace Cubex\Tests\Supporting\Ui\TestElement;

use Cubex\Context\ContextAware;
use Cubex\Context\ContextAwareTrait;
use Packaged\Ui\Element;

class TestUiElement extends Element implements ContextAware
{
  use ContextAwareTrait;

  protected $_content;

  /**
   * @param string $content
   *
   * @return TestUiElement
   */
  public function setContent($content)
  {
    $this->_content = $content;
    return $this;
  }

  public function getContent()
  {
    return $this->_content;
  }
}
