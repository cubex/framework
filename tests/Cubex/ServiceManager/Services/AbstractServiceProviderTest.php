<?php

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
     * @var $abstract \Cubex\ServiceManager\Services\AbstractServiceProvider
     */
    $cubex  = new \Cubex\Cubex();
    $config = new \Packaged\Config\Provider\ConfigSection('ser', ['t' => '1']);

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
