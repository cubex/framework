<?php
namespace Cubex\Auth;

interface IAuthedUser
{
  /**
   * Unique ID for the logged in user
   *
   * @return int|string
   */
  public function getUserId();

  /**
   * Username for the logged in user
   *
   * @return string
   */
  public function getUsername();

  /**
   * Get a cached property of the authed user (as populated from login)
   *
   * @param $key
   * @param $default
   *
   * @return mixed
   */
  public function getProperty($key, $default = null);

  /**
   * Get all cached properties
   *
   * @return array
   */
  public function getProperties();

  /**
   * @return string Serialized representation of the authed user
   */
  public function serialize();

  /**
   * @param $data string Serialized representation of the authed user
   *
   * @return IAuthedUser|self
   */
  public function unserialize($data);
}
