<?php
namespace CubexTest\Cubex\I18n;

use Cubex\CubexAwareTrait;
use Cubex\I18n\TranslationTrait;
use CubexTest\InternalCubexTestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class TranslationTraitTestInternal extends InternalCubexTestCase
{
  protected function _createInstance($locale = 'en_EN')
  {
    $instance = new TranslateTestClass();
    $instance->setCubex($this->newCubexInstace());

    if($locale === null)
    {
      return $instance;
    }

    $translator = new Translator($locale);
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource(
      'array',
      [
        'Hello World!' => 'Hello World!',
        'Hello %s!'    => 'Hello %s!',
      ],
      'en_EN'
    );
    $translator->addResource(
      'array',
      [
        'Hello World!' => 'Bonjour!',
        'Hello %s!'    => 'Bonjour %s!',
      ],
      'fr_FR'
    );
    $instance->setTranslator($translator);
    return $instance;
  }

  public function testTranslation()
  {
    $no = $this->_createInstance(null);
    $this->assertEquals('Hello World!', $no->t('Hello World!'));
    $this->assertEquals('Missing Content', $no->t('Missing Content'));
    $this->assertEquals('Hello Brooke!', $no->t('Hello %s!', 'Brooke'));

    $en = $this->_createInstance('en_EN');
    $this->assertEquals('Hello World!', $en->t('Hello World!'));
    $this->assertEquals('Missing Content', $en->t('Missing Content'));
    $this->assertEquals('Hello Brooke!', $en->t('Hello %s!', 'Brooke'));

    $fr = $this->_createInstance('fr_FR');
    $this->assertEquals('Bonjour!', $fr->t('Hello World!'));
    $this->assertEquals('Bonjour Brooke!', $fr->t('Hello %s!', 'Brooke'));
  }

  public function testPluralTranslation()
  {
    $no = $this->_createInstance(null);
    $this->assertEquals(
      'You have %d task',
      $no->tp('You have %d task(s)', 1)
    );

    $en = $this->_createInstance('en_EN');
    $this->assertEquals(
      'You have %d task',
      $en->tp('You have %d task(s)', 1)
    );
    $this->assertEquals(
      'You have 1 task',
      $en->tp('You have %d task(s)', 1, 1)
    );
    $this->assertEquals(
      'You have 2 tasks',
      $en->tp('You have %d task(s)', 2, 2)
    );
  }

  public function testChoice()
  {
    $tpl = '{0} There are no apples|' .
      '{1} There is one apple|' .
      ']1,Inf[ There are %d apples';

    $no = $this->_createInstance(null);
    $this->assertEquals('There are no apples', $no->choice($tpl, 0));
    $this->assertEquals('There is one apple', $no->choice($tpl, 1));
    $this->assertEquals('There are 3 apples', $no->choice($tpl, 3, 3));

    $en = $this->_createInstance('en_EN');
    $this->assertEquals('There are no apples', $en->choice($tpl, 0));
    $this->assertEquals('There is one apple', $en->choice($tpl, 1));
    $this->assertEquals('There are 3 apples', $en->choice($tpl, 3, 3));
  }

  public function testTranslatorAutoLoad()
  {
    $translator = new Translator('en_EN');
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource(
      'array',
      [
        'Hello World!' => 'Hello World!',
      ],
      'en_EN'
    );

    $instance = new TranslateTestClass();
    $instance->setCubex($this->newCubexInstace());
    $instance->getCubex()->instance('i18n.translator', $translator);
    $instance->getTranslator();
  }
}

class TranslateTestClass
{
  use CubexAwareTrait;
  use TranslationTrait;
}
