<?php
namespace Cubex\Http;

class Response extends \Symfony\Component\HttpFoundation\Response
{
  public function __construct($content = '', $status = 200, $headers = array())
  {
    parent::__construct('', $status, $headers);
    $this->from($content);
  }

  /**
   * Automatically detect the source, and create the correct response type
   *
   * @param $source
   *
   * @return $this
   */
  public function from($source)
  {
    if(is_object($source) || is_array($source))
    {
      if(method_exists($source, '__toString'))
      {
        $this->setContent((string)$source);
      }
      else
      {
        $this->fromJson($source);
      }
    }
    else
    {
      $this->setContent($source);
    }

    return $this;
  }

  /**
   * Set the response to be a json representation of the object
   *
   * @param $object
   *
   * @return $this
   */
  public function fromJson($object)
  {
    $response = \json_encode($object);

    // Prevent content sniffing attacks by encoding "<" and ">", so browsers
    // won't try to execute the document as HTML
    $response = \str_replace(
      array('<', '>'),
      array('\u003c', '\u003e'),
      $response
    );

    $this->setContent($response);
    $this->headers->set("Content-Type", "application/json");

    return $this;
  }

  /**
   * Set the response to be a json encoded object using the JSONP standard;
   * http://bob.ippoli.to/archives/2005/12/05/remote-json-jsonp/
   *
   * @param string $responseKey
   * @param object $object
   *
   * @return $this
   */
  public function fromJsonp($responseKey, $object)
  {
    $responseObject = \json_encode($object);
    $response       = "{$responseKey}({$responseObject})";

    // Prevent content sniffing attacks by encoding "<" and ">", so browsers
    // won't try to execute the document as HTML
    $response = \str_replace(
      array('<', '>'),
      array('\u003c', '\u003e'),
      $response
    );

    $this->setContent($response);
    $this->headers->set("Content-Type", "application/json");

    return $this;
  }

  /**
   * Set the response to be plain text
   *
   * @param $text
   *
   * @return $this
   */
  public function fromText($text)
  {
    $this->setContent($text);
    $this->headers->set("Content-Type", "text/plain");

    return $this;
  }

  /**
   * Add Cubex Headers before sending the response
   *
   * @inheritdoc
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function send()
  {
    $this->setCubexHeaders();
    return parent::send();
  }

  /**
   * Define Cubex Headers
   *
   * Automatically called by ->send()
   */
  public function setCubexHeaders()
  {
    //Add the exec time as a header if PHP_START has been defined by the project
    if(defined('PHP_START'))
    {
      $this->headers->set(
        "X-Execution-Time",
        number_format((microtime(true) - PHP_START) * 1000, 3) . ' ms'
      );
    }
  }

  /**
   * Enable zlib output compression
   *
   * @param null|bool $force null to enable if extension loaded
   *
   * @return bool
   */
  public function enableGzip($force = null)
  {
    if($force === true || ($force === null && extension_loaded("zlib")))
    {
      if(!headers_sent())
      {
        ini_set('zlib.output_compression', 'On');
      }
      return true;
    }
    return false;
  }
}
