<?php
namespace Cubex\ViewModel;

class ArrayModel extends ViewModel
{
  protected array $_data = [];

  public function clear()
  {
    $this->_data = [];
    return $this;
  }

  public function set(array $data = [])
  {
    $this->_data = $data;
    return $this;
  }

  public function addItem($value, string $key = null)
  {
    if($key === null)
    {
      $this->_data[] = $value;
    }
    else
    {
      $this->_data[$key] = $value;
    }
    return $this;
  }

  public function getData()
  {
    return $this->_data;
  }

  public function jsonSerialize(): mixed
  {
    return $this->_data;
  }

}
