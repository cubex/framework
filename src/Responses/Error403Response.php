<?php
namespace Cubex\Responses;

use Cubex\Http\Response;

class Error403Response extends Response
{
  public function __construct($content = '', $status = 403, $headers = array())
  {
    $content = '<h2>Error 403 - Access Forbidden</h2>';
    $content .= '<p>You do not have permission';
    $content .= ' to access the page you requested</p>';
    parent::__construct($content, $status, $headers);
  }
}
