<?php
namespace CubexTest\Cubex\View;

use Cubex\View\ViewModelHelperTrait;

class ViewModelHelperTraitTest extends \PHPUnit_Framework_TestCase
{
  public function testBasics()
  {
    $class = new TestableViewModelHelperTrait();
    $this->assertEquals(0, $class->count('banners'));
    $this->assertFalse($class->has('banners'));
    $this->assertEquals('default', $class->get('banners', 'default'));
    $class->addBanner('468x60');
    $this->assertEquals(1, $class->count('banners'));
    $this->assertTrue($class->has('banners'));
    $this->assertEquals(['468x60'], $class->get('banners'));
  }

  public function testMagic()
  {
    $class = new TestableViewModelHelperTrait();
    $this->assertEquals(0, $class->countBanners());
    $this->assertFalse($class->hasBanners());
    $this->assertEquals('default', $class->getBanners('default'));
    $class->addBanner('468x60');
    $this->assertEquals(1, $class->countBanners());
    $this->assertTrue($class->hasBanners());
    $this->assertEquals(['468x60'], $class->getBanners());
  }

  public function testMagicException()
  {
    $this->setExpectedException('\Exception', 'Unsupported method random');
    $class = new TestableViewModelHelperTrait();
    $class->random();
  }
}

/**
 * @method bool hasBanners
 * @method int countBanners
 * @method array getBanners($default = null)
 *
 * @method null random
 */
class TestableViewModelHelperTrait
{
  use ViewModelHelperTrait;

  protected $_banners;

  public function addBanner($banner)
  {
    $this->_banners[] = $banner;
    return $this;
  }
}
