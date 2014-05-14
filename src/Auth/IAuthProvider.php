<?php
namespace Cubex\Auth;

interface IAuthProvider
{
  /**
   * @param       $username
   * @param       $password
   * @param array $options
   *
   * @return IAuthedUser|null
   */
  public function login($username, $password, array $options = null);

  /**
   * @return bool
   */
  public function logout();

  /**
   * Retrieve the logged in user dynamically, e.g. off sessions or ips
   *
   * @return IAuthedUser|null
   */
  public function retrieveUser();
}
