<?php
namespace Cubex\Responses;

use Cubex\Http\Response;

class CsvResponse extends Response
{
  protected $_filename;

  public function __construct($content = '', $status = 200, $headers = [])
  {
    parent::__construct('', $status, $headers);
    $this->setContent($content);
  }

  public function send()
  {
    $this->headers->set('Content-Type', 'text/csv');
    $this->headers->set('Content-Description', 'CSV Download');
    $this->headers->set(
      'Content-Disposition',
      'attachment; filename=' . $this->getFilename()
    );
    $this->headers->set('Content-Transfer-Encoding', 'binary');
    $this->headers->set('Pragma', 'no-cache');
    $this->headers->set('Expires', '0');

    return parent::send();
  }

  public function getFilename()
  {
    return $this->_filename;
  }

  public function setFilename($filename)
  {
    $this->_filename = $filename;
    return $this;
  }

  public function setContent($content)
  {
    if(!is_array($content) && !is_object($content) && !empty($content))
    {
      throw new \RuntimeException(
        "You must specify an array or object when using a csv response"
      );
    }
    $this->content = $content;
    return $this;
  }

  public function sendContent()
  {
    if(is_array($this->content) || is_object($this->content))
    {
      $out = fopen('php://output', 'w');
      foreach($this->content as $row)
      {
        fputcsv($out, (array)$row);
      }
      fclose($out);
      return $this;
    }
    return parent::sendContent();
  }

  public function getContent()
  {
    ob_start();
    $this->sendContent();
    return ob_get_clean();
  }
}
