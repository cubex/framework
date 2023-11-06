<?php
namespace Cubex\ViewModel;

use Cubex\CubexAware;
use Cubex\CubexAwareTrait;
use Packaged\Context\ContextAware;
use Packaged\Context\ContextAwareTrait;
use Packaged\Context\WithContextTrait;
use Packaged\Helpers\Objects;

class ViewModel implements Model, ContextAware, CubexAware
{
  use CubexAwareTrait;

  protected string $_defaultView;
  /**
   * @var bool locking property modification
   */
  private bool $_locked;
  private array $_lockedData = [];

  use ContextAwareTrait;
  use WithContextTrait;

  public function jsonSerialize(): mixed
  {
    $values = $this->_locked ? $this->_lockedData : Objects::propertyValues($this);
    return empty($values) ? $this : $values;
  }

  public function setView(string $viewClass)
  {
    $this->_defaultView = $viewClass;
    return $this;
  }

  public function createView(string $overrideViewClass = null): ?View
  {
    if($overrideViewClass === null && !empty($this->_defaultView))
    {
      $overrideViewClass = $this->_defaultView;
    }

    if($overrideViewClass === '' || !class_exists($overrideViewClass))
    {
      throw new \Exception("Invalid view class provided '$overrideViewClass'");
    }

    $view = $this->hasCubex() ? $this->getCubex()->retrieve($overrideViewClass, [], false, false) :
      new $overrideViewClass();
    if($view instanceof View)
    {
      $view->setModel($this);
    }

    if($view instanceof ContextAware && $this->hasContext())
    {
      $view->setContext($this->getContext());
    }

    return $view;
  }

  public function lock()
  {
    foreach(Objects::propertyValues($this) as $k => $v)
    {
      $this->_lockedData[$k] = $v;
      unset($this->$k);
    }
    $this->_locked = true;
    return $this;
  }

  public function __get(string $propertyName): mixed
  {
    if($this->_locked && isset($this->_lockedData[$propertyName]))
    {
      return $this->_lockedData[$propertyName];
    }
    return null;
  }

  public function __set(string $propertyName, $value): void
  {
    if($this->_locked)
    {
      $className = get_called_class();
      throw new \Exception("Cannot set property {$propertyName}. {$className} is immutable.");
    }
    $this->$propertyName = $value;
  }
}
