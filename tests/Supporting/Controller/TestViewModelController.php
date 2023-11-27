<?php

namespace Cubex\Tests\Supporting\Controller;

use Cubex\Controller\Controller;
use Cubex\Tests\Supporting\ViewModel\TestDefaultView;
use Cubex\Tests\Supporting\ViewModel\TestDefaultViewModel;
use Cubex\Tests\Supporting\ViewModel\TestViewModel;

class TestViewModelController extends Controller
{
  protected function _generateRoutes()
  {
    yield self::_route('/test', 'test');
    yield self::_route('/test-data', 'testData');
    return 'default';
  }

  public function processDefault()
  {
    $cubex = @$this->_cubex();
    return $cubex->resolve(TestDefaultView::class);
  }

  public function processTest()
  {
    $cubex = @$this->_cubex();
    return $cubex->resolve(TestViewModel::class);
  }

  public function processTestData()
  {
    $cubex = @$this->_cubex();
    $viewModel = $cubex->resolve(TestViewModel::class);
    $viewModel->test = 'Test Data';
    return $viewModel;
  }
}
