<?php
namespace Cubex\Ui;

use DivisionByZeroError;

class ViewElement
{
  public function render(): SafeHtml{
    new SafeHtml(include(''));
  }
}
