<?php

namespace Cubex\Tests\Supporting\ViewModel;

use Cubex\ViewModel\ViewModel;

class TestViewModel extends ViewModel
{
  protected string $_defaultView = TestView::class;

  public string $test = 'Test';
}
