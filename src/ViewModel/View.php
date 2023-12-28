<?php
namespace Cubex\ViewModel;

use Packaged\Ui\Renderable;

/**
 * A view must be constructed with a model
 */
interface View extends Renderable
{
  public function setModel(Model $data);
}
