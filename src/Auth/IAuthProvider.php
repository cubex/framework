<?php
namespace Cubex\Auth;

interface IAuthProvider
{
  /**
   * @param       $username
   * @param       $password
   * @param array $options
   *
   * @return IAuthedUser
   *
   * @throws \Exception
   */
  public function login($username, $password, array $options = null);

  /**
   * @return bool
   */
  public function logout();

  /**
   * Retrieve the logged in user dynamically, e.g. off sessions or ips
   *
   * @return IAuthedUser
   *
   * @throws \Exception
   */
  public function retrieveUser();

  /**
   * @param       $username
   * @param array $options
   *  ['callback' => closure]
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function forgottenPassword($username, array $options = null);
}
