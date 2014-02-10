<?php
namespace Cubex\Responses;

use Cubex\Http\Response;

class Error404Response extends Response
{
  public function __construct($content = '', $status = 404, $headers = array())
  {
    $content = '<h2>The page you requested was not found</h2>';
    $content .= '<p>You may have clicked an expired link or mistyped the ';
    $content .= 'address. Some web addresses are case sensitive</p>';
    parent::__construct($content, $status, $headers);
  }
}
