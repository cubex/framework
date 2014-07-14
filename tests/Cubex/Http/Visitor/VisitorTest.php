<?php

class VisitorTest extends \CubexTestCase
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
    $cubex   = $this->newCubexInstace();
    $request = new \Cubex\Http\Request();
    $server  = array('REMOTE_ADDR' => $remoteAddr);
    $request->initialize(array(), array(), array(), array(), array(), $server);
    $cubex->instance('request', $request);

    if($config === null)
    {
      $config = new \Packaged\Config\Provider\ConfigSection('http_visitor', []);
    }

    $cubex->getConfiguration()->addSection($config);

    $visitor = new \Cubex\Http\Visitor\Visitor($request, $cubex);
    $visitor->setClientIp($remoteAddr);
    $this->assertEquals($country, $visitor->getCountry());
    $this->assertEquals($city, $visitor->getCity());
    $this->assertEquals($region, $visitor->getRegion());
  }

  public function testFailover()
  {
    $config = new \Packaged\Config\Provider\ConfigSection(
      'http_visitor',
      ['failover' => 'http.failover']
    );

    $failover = new \Cubex\Http\Visitor\MockVisitorInfo(
      'IE',
      'Killarney',
      'Kerry'
    );
    $failover->setClientIp('123.123.123.123');
    $failover->configure(
      $config
    );
    $cubex   = $this->newCubexInstace();
    $request = new \Cubex\Http\Request();
    $server  = ['REMOTE_ADDR' => '123.123.123.123'];
    $request->initialize(array(), array(), array(), array(), array(), $server);
    $cubex->getConfiguration()->addSection($config);
    $cubex->instance('request', $request);
    $cubex->instance('http.failover', $failover);

    $visitor = new \Cubex\Http\Visitor\Visitor($request, $cubex);
    $this->assertEquals('IE', $visitor->getCountry());
    $this->assertEquals('Killarney', $visitor->getCity());
    $this->assertEquals('Kerry', $visitor->getRegion());

    $visitor = new \Cubex\Http\Visitor\Visitor($request, $cubex);
    $visitor->setFailoverLookup($failover);
    $this->assertEquals('IE', $visitor->getCountry());
    $this->assertEquals('Killarney', $visitor->getCity());
    $this->assertEquals('Kerry', $visitor->getRegion());
  }

  public function testFromAppEngine()
  {
    $old                        = isset($_SERVER['SERVER_SOFTWARE']) ?
      $_SERVER['SERVER_SOFTWARE'] : null;
    $_SERVER['SERVER_SOFTWARE'] = 'Google App Engine/1.0';
    $cubex                      = $this->newCubexInstace();
    $request                    = new \Cubex\Http\Request();
    $server                     = [
      'REMOTE_ADDR'         => '123.123.123.123',
      'SERVER_SOFTWARE'     => 'Google App Engine/1.0',
      'X-AppEngine-Country' => 'US',
      'X-AppEngine-City'    => 'Mountain View',
      'X-AppEngine-Region'  => 'CA',
    ];
    $request->initialize(array(), array(), array(), array(), array(), $server);
    $cubex->instance('request', $request);

    $visitor = new \Cubex\Http\Visitor\Visitor($request, $cubex);
    $this->assertEquals('US', $visitor->getCountry());
    $this->assertEquals('Mountain View', $visitor->getCity());
    $this->assertEquals('CA', $visitor->getRegion());

    if($old === null)
    {
      unset($_SERVER['SERVER_SOFTWARE']);
    }
    else
    {
      $_SERVER['SERVER_SOFTWARE'] = $old;
    }
  }

  public function visitorProvider()
  {
    return [
      ['127.0.0.1', 'GB', 'London', 'eng'],
      [
        '127.0.0.1',
        'UK',
        'Portsmouth',
        'england',
        new \Packaged\Config\Provider\ConfigSection(
          'http_visitor',
          ['city' => 'Portsmouth', 'country' => 'UK', 'region' => 'england']
        )
      ],
      [
        '208.67.222.222',
        'US',
        'San Francisco',
        'CA',
        new \Packaged\Config\Provider\ConfigSection(
          'http_visitor',
          ['whois' => true]
        )
      ]
    ];
  }
}
