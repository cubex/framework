<?php
namespace CubexTest\Cubex\ServiceManager\Services;

use Cubex\Cubex;
use Cubex\ServiceManager\Services\AbstractServiceProvider;
use Packaged\Config\Provider\ConfigSection;

class AbstractServiceProviderTest extends \PHPUnit_Framework_TestCase
{
  public function testAbstracts()
  {
    $abstract = $this->getMockForAbstractClass(
      '\Cubex\ServiceManager\Services\AbstractServiceProvider'
    );
    $this->assertInstanceOf(
      '\Cubex\ServiceManager\IServiceProvider',
      $abstract
    );
    $this->assertInstanceOf('\Cubex\ICubexAware', $abstract);
    /**
     * @var $abstract AbstractServiceProvider
     */
    $cubex  = new Cubex();
    $config = new ConfigSection('ser', ['t' => '1']);

    $abstract->boot($cubex, $config);

    $this->assertSame($cubex, $abstract->getCubex());
    $this->assertSame($config, $abstract->getConfig());
    $this->assertNull($abstract->register([]));
    $this->assertNull($abstract->shutdown());

    $this->assertNull($abstract->getConfigItem('missing'));
    $this->assertEquals(1, $abstract->getConfigItem('t'));
    $this->assertEquals(2, $abstract->getConfigItem('ts', 2));
  }
}
