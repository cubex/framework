<?php
namespace namespaced\Views;

use Cubex\View\ViewModel;

class TestableView extends ViewModel
{
  public function render()
  {
    return 'testable';
  }
}
