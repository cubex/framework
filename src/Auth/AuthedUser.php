<?php
namespace Cubex\Auth;

use Packaged\Helpers\ValueAs;

class AuthedUser implements IAuthedUser
{
  protected $_data = [];

  public function __construct(
    $username = null, $userId = 0, array $properties = []
  )
  {
    $this->_data['userId'] = $userId;
    $this->_data['username'] = $username;
    $this->_data['data'] = $properties;
  }

  protected function _getData($key, $default = null)
  {
    return isset($this->_data[$key]) ? $this->_data[$key] : $default;
  }

  /**
   * Unique ID for the logged in user
   *
   * @return int|string
   */
  public function getUserId()
  {
    return $this->_getData('userId', 0);
  }

  /**
   * Username for the logged in user
   *
   * @return string
   */
  public function getUsername()
  {
    return $this->_getData('username', '');
  }

  /**
   * Get a cached property of the authed user (as populated from login)
   *
   * @param $key
   * @param $default
   *
   * @return mixed
   */
  public function getProperty($key, $default = null)
  {
    $data = $this->_getData('data', []);
    return isset($data[$key]) ? $data[$key] : $default;
  }

  /**
   * Set a cached property on the authed user
   *
   * @param $key
   * @param $value
   *
   * @return $this
   */
  public function setProperty($key, $value)
  {
    $this->_data['data'][$key] = $value;
    return $this;
  }

  /**
   * Get all cached properties
   *
   * @return array
   */
  public function getProperties()
  {
    return (array)$this->_data['data'];
  }

  /**
   * @return string Serialized representation of the authed user
   */
  public function serialize()
  {
    return json_encode($this->_data);
  }

  /**
   * @param $data string Serialized representation of the authed user
   *
   * @throws \Exception
   *
   * @return IAuthedUser|self
   */
  public function unserialize($data)
  {
    $json = json_decode($data);
    if(json_last_error() === JSON_ERROR_NONE)
    {
      $this->_data = (array)$json;
      $this->_data['data'] = ValueAs::arr($this->_data['data'], []);
    }
    else
    {
      throw new \Exception(
        'Unable to unserialize login cookie',
        json_last_error()
      );
    }
    return $this;
  }

  /**
   * Create an Authed User from the serialized string
   *
   * @param $data
   *
   * @return IAuthedUser
   * @throws \Exception
   */
  public static function fromString($data)
  {
    $user = new static;
    return $user->unserialize($data);
  }
}
