<?php
namespace Project\Layout;

use Cubex\Ui\UiElement;

class Layout extends UiElement
{
  public function time()
  {
    return date("Y-m-d");
  }
}
