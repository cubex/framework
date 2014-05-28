<?php
namespace Cubex\Http;

use Illuminate\Support\Contracts\RenderableInterface;

class Response extends \Symfony\Component\HttpFoundation\Response
{
  public function __construct($content = '', $status = 200, $headers = array())
  {
    parent::__construct('', $status, $headers);
    $this->from($content);
  }

  protected $_originalSource;

  /**
   * Automatically detect the source, and create the correct response type
   *
   * @param $source
   *
   * @return $this
   */
  public function from($source)
  {
    $this->_originalSource = $source;

    if(is_object($source) || is_array($source))
    {
      if($source instanceof RenderableInterface)
      {
        $this->setContent($source->render());
      }
      else if(method_exists($source, '__toString'))
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
    $this->_originalSource = $object;
    $response              = \json_encode($object);

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
    $this->_originalSource = $object;
    $responseObject        = \json_encode($object);
    $response              = "{$responseKey}({$responseObject})";

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
    $this->_originalSource = $text;
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
   * Retrieve the original data used to create the response
   *
   * @return mixed
   */
  public function getOriginalResponse()
  {
    return $this->_originalSource;
  }
}
