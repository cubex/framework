<?php

namespace Cubex\Tests\ViewModel;

use Cubex\ViewModel\TemplatedViewModel;
use PHPUnit\Framework\TestCase;

class TemplatedViewModelTest extends TestCase
{
  public function testAddVariant()
  {
    $viewModel = new TemplatedViewModel();
    $viewModel->addVariant('test');
    $this->assertEquals(['test.phtml'], $viewModel->getVariants());
    $viewModel->addVariant('test2');
    $this->assertEquals(['test2.phtml', 'test.phtml'], $viewModel->getVariants());
    $viewModel->addVariant('test3', 'html');
    $this->assertEquals(['test3.html', 'test2.phtml', 'test.phtml'], $viewModel->getVariants());
  }

  public function testClearVariants()
  {
    $viewModel = new TemplatedViewModel();
    $viewModel->addVariant('test');
    $viewModel->clearVariants();
    $this->assertEmpty($viewModel->getVariants());
  }
}
