<?php
namespace Cubex\Tests\Supporting\Ui\TestElement;

use Cubex\Ui\UiElement;

class TestUiElement extends UiElement
{
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
