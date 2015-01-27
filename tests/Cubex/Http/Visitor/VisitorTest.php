<?php
namespace CubexTest\Cubex\Http\Visitor;

use Cubex\Http\Request;
use Cubex\Http\Visitor\MockVisitorInfo;
use Cubex\Http\Visitor\Visitor;
use CubexTest\InternalCubexTestCase;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Helpers\System;

class VisitorTestInternal extends InternalCubexTestCase
{
  /**
   * @param      $remoteAddr
   * @param      $country
   * @param      $city
   * @param      $region
   * @param null $config
   *
   * @dataProvider visitorProvider
   */
  public function testVisitor(
    $remoteAddr, $country, $city, $region, $config = null
  )
  {
    $cubex = $this->newCubexInstace();
    $request = new Request();
    $server = ['REMOTE_ADDR' => $remoteAddr];
    $request->initialize([], [], [], [], [], $server);
    $cubex->instance('request', $request);

    if($config === null)
    {
      $config = new ConfigSection('http_visitor', []);
    }

    $cubex->getConfiguration()->addSection($config);

    $visitor = new Visitor($request, $cubex);
    $visitor->setClientIp($remoteAddr);
    $this->assertEquals($country, $visitor->getCountry());
    $this->assertEquals($city, $visitor->getCity());
    $this->assertEquals($region, $visitor->getRegion());
  }

  public function testFailover()
  {
    $config = new ConfigSection(
      'http_visitor',
      ['failover' => 'http.failover']
    );

    $failover = new MockVisitorInfo(
      'IE',
      'Killarney',
      'Kerry'
    );
    $failover->setClientIp('123.123.123.123');
    $failover->configure(
      $config
    );
    $cubex = $this->newCubexInstace();
    $request = new Request();
    $server = ['REMOTE_ADDR' => '123.123.123.123'];
    $request->initialize([], [], [], [], [], $server);
    $cubex->getConfiguration()->addSection($config);
    $cubex->instance('request', $request);
    $cubex->instance('http.failover', $failover);

    $visitor = new Visitor($request, $cubex);
    $this->assertEquals('IE', $visitor->getCountry());
    $this->assertEquals('Killarney', $visitor->getCity());
    $this->assertEquals('Kerry', $visitor->getRegion());

    $visitor = new Visitor($request, $cubex);
    $visitor->setFailoverLookup($failover);
    $this->assertEquals('IE', $visitor->getCountry());
    $this->assertEquals('Killarney', $visitor->getCity());
    $this->assertEquals('Kerry', $visitor->getRegion());
  }

  public function testFromAppEngine()
  {
    $cubex = $this->newCubexInstace();
    $request = new Request();
    $server = [
      'REMOTE_ADDR'              => '123.123.123.123',
      'SERVER_SOFTWARE'          => 'Google App Engine/1.0',
      'HTTP_X_APPENGINE_COUNTRY' => 'US',
      'HTTP_X_APPENGINE_CITY'    => 'Mountain View',
      'HTTP_X_APPENGINE_REGION'  => 'CA',
    ];
    $request->initialize([], [], [], [], [], $server);
    $cubex->instance('request', $request);

    $visitor = new Visitor($request, $cubex);
    $this->assertEquals('US', $visitor->getCountry());
    $this->assertEquals('Mountain View', $visitor->getCity());
    $this->assertEquals('CA', $visitor->getRegion());
  }

  public function testFromModGeoIp()
  {
    $cubex = $this->newCubexInstace();
    $request = new Request();
    $server = [
      'REMOTE_ADDR'        => '123.123.123.123',
      'GEOIP_ADDR'         => '123.123.123.123',
      'GEOIP_COUNTRY_CODE' => 'US',
      'GEOIP_CITY'         => 'Mountain View',
      'GEOIP_REGION_NAME'  => 'CA',
    ];
    $request->initialize([], [], [], [], [], $server);
    $cubex->instance('request', $request);

    $visitor = new Visitor($request, $cubex);
    $this->assertEquals('US', $visitor->getCountry());
    $this->assertEquals('Mountain View', $visitor->getCity());
    $this->assertEquals('CA', $visitor->getRegion());
  }

  public function visitorProvider()
  {
    $whois = System::commandExists('whois');
    return [
      ['127.0.0.1', 'GB', 'London', 'eng'],
      [
        '127.0.0.1',
        'UK',
        'Portsmouth',
        'england',
        new ConfigSection(
          'http_visitor',
          ['city' => 'Portsmouth', 'country' => 'UK', 'region' => 'england']
        )
      ],
      [
        '208.67.222.222',
        $whois ? 'US' : 'GB',
        $whois ? 'San Francisco' : 'London',
        $whois ? 'CA' : 'eng',
        new ConfigSection(
          'http_visitor',
          ['whois' => true]
        )
      ]
    ];
  }
}
