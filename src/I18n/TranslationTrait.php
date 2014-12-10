<?php
namespace Cubex\I18n;

use Cubex\Cubex;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

trait TranslationTrait
{
  /**
   * Retrieve the cubex application
   *
   * @return Cubex
   *
   * @throws \RuntimeException
   */
  abstract public function getCubex();

  /**
   * @var Translator
   */
  protected $_translator;

  /**
   * @var bool
   */
  protected $_attemptedBuild = false;

  /**
   * Set the Translator to be used
   *
   * @param Translator $translator
   *
   * @return $this
   */
  public function setTranslator(Translator $translator)
  {
    $this->_translator = $translator;
    return $this;
  }

  /**
   * Retrieve the Translation Loader
   *
   * @returns Translator
   *
   * @throws \RuntimeException
   */
  public function getTranslator()
  {
    //Attempt to load from Cubex
    if($this->_translator === null && !$this->_attemptedBuild)
    {
      $this->_attemptedBuild = true;
      $translator = $this->getCubex()->make('i18n.translator');
      if($translator instanceof Translator)
      {
        $this->setTranslator($translator);
      }
    }

    if($this->_translator === null)
    {
      throw new \RuntimeException("No Translator has been configured");
    }
    return $this->_translator;
  }

  /**
   * Translate string to locale
   *
   * If multiple parameters are set, these values will be sprintf'ed on the msg
   *
   * @param $message string $string
   *
   * @return string
   */
  public function t($message)
  {
    try
    {
      $result = $this->getTranslator()->trans($message);
    }
    catch(\Exception $e)
    {
      $result = $message;
    }

    if(func_num_args() > 1)
    {
      $args = func_get_args();
      array_shift($args);
      $result = vsprintf($result, $args);
    }

    return $result;
  }

  /**
   * Translate plural
   *
   * If multiple parameters are set, these values will be sprintf'ed on the msg
   *
   * @param      $singular
   * @param null $plural
   * @param int  $number
   *
   * @return string
   */
  public function p($singular, $plural = null, $number = 0)
  {
    try
    {
      $result = $this->getTranslator()->transChoice(
        "$singular|$plural",
        $number
      );
    }
    catch(\Exception $e)
    {
      $result = $number == 1 ? $singular : $plural;
    }

    if(func_num_args() > 3)
    {
      $args = func_get_args();
      array_shift($args);
      array_shift($args);
      array_shift($args);
      $result = vsprintf($result, $args);
    }

    return $result;
  }

  /**
   *
   * Translate plural, converting (s) to '' or 's'
   *
   * @param string $text   Text to translate
   * @param int    $number Quantity to work with
   *
   * @return string Translated text
   *
   */
  public function tp($text, $number)
  {
    if(func_num_args() > 2)
    {
      $args = func_get_args();
      array_shift($args);
      $singular = str_replace('(s)', '', $text);
      $plural = str_replace('(s)', 's', $text);
      array_unshift($args, $singular, $plural);
      return call_user_func_array([$this, 'p'], $args);
    }

    return $this->p(
      str_replace('(s)', '', $text),
      str_replace('(s)', 's', $text),
      $number
    );
  }

  /**
   * Translates the given choice message by choosing a
   * translation according to a number.
   *
   * If multiple parameters are set, these values will be sprintf'ed on the msg
   *
   * @param     $messageTemplate
   * @param int $number
   *
   * @return string
   * @throws \RuntimeException
   */
  public function choice($messageTemplate, $number = 0)
  {
    try
    {
      $result = $this->getTranslator()->transChoice($messageTemplate, $number);
    }
    catch(\Exception $e)
    {
      $selector = new MessageSelector();
      $result = $selector->choose($messageTemplate, $number, 'en_EN');
    }

    if(func_num_args() > 2)
    {
      $args = func_get_args();
      array_shift($args);
      array_shift($args);
      $result = vsprintf($result, $args);
    }

    return $result;
  }
}
