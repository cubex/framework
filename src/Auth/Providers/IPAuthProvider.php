<?php
namespace Cubex\Auth\Providers;

use Cubex\Auth\AuthedUser;
use Cubex\Auth\IAuthedUser;
use Cubex\Auth\IAuthProvider;
use Cubex\CubexAwareTrait;
use Cubex\Http\Request;
use Cubex\ICubexAware;

/**
 * Example Ini Data
 * [ipauth]
 * jdi[username] = Brooke
 * jdi[userid] = 1
 * jdi[display] = Brooke Default
 * 127.0.0.1[alias] = jdi
 * 127.0.0.1[display] = Brooke Local
 * 192.168.0.112[alias] = jdi
 * 192.168.0.110[username] = Davide
 * 192.168.0.110[userid] = 2
 * 192.168.0.153[username] = James
 * 192.168.0.153[userid] = 3
 */
class IPAuthProvider implements IAuthProvider, ICubexAware
{
  use CubexAwareTrait;

  /**
   * @param       $username
   * @param       $password
   * @param array $options
   *
   * @return IAuthedUser
   *
   * @throws \Exception
   * @throws \RuntimeException
   */
  public function login($username, $password, array $options = null)
  {
    //Ignore any user input and return the IP specific user
    return $this->retrieveUser();
  }

  /**
   * @return bool
   */
  public function logout()
  {
    //You are unable to logout of IP Auth
    return false;
  }

  /**
   * Retrieve the logged in user dynamically, e.g. off sessions or ips
   *
   * @return IAuthedUser
   * @throws \Exception
   * @throws \RuntimeException
   */
  public function retrieveUser()
  {
    $request = $this->getCubex()->make('request');
    if($request instanceof Request)
    {
      $config = $this->getCubex()->getConfiguration()->getSection('ipauth');

      $info = $config->getItem($request->getClientIp(), null);
      if(!isset($info))
      {
        //IP not configured
        throw new \Exception("Unauthorized IP");
      }
      if(isset($info['alias']))
      {
        //Load the aliased user, merging the IP specific data ontop
        $info = array_merge($config->getItem($info['alias'], []), $info);
      }

      if(isset($info['username']))
      {
        if(!isset($info['userid']))
        {
          $info['userid'] = 0;
        }
        return new AuthedUser(
          $info['username'], $info['userid'], $info
        );
      }
      else
      {
        throw new \Exception("Invalid IP Configuration");
      }
    }
    throw new \RuntimeException("Unable to retrieve the users IP");
  }

  public function forgottenPassword($username, array $options = null)
  {
    throw new \Exception('Forgotten Password is not available');
  }
}
