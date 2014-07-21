<?php

class MaxmindVisitorTest extends \CubexTestCase
{
  protected $_geoipdb;

  protected function setUp()
  {
    $dbgz = 'http://geolite.maxmind.com/download/'
      . 'geoip/database/GeoLite2-City.mmdb.gz';

    $filename       = build_path(sys_get_temp_dir(), 'GeoLite2-City.mmdb.gz');
    $this->_geoipdb = substr($filename, 0, -3);

    if(!file_exists($this->_geoipdb))
    {
      file_put_contents($filename, file_get_contents($dbgz));

      $file = gzopen($filename, 'rb');
      $out  = fopen($this->_geoipdb, 'wb');
      while(!gzeof($file))
      {
        fwrite($out, gzread($file, 4096));
      }
      fclose($out);
      gzclose($file);
      unlink($filename);
    }
  }

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
    if(!file_exists($this->_geoipdb))
    {
      $this->markTestSkipped("GeoIP Database Not Downloaded");
      return;
    }

    $cubex   = $this->newCubexInstace();
    $request = new \Cubex\Http\Request();
    $server  = array('REMOTE_ADDR' => $remoteAddr);
    $request->initialize(array(), array(), array(), array(), array(), $server);
    $cubex->instance('request', $request);

    if($config === null)
    {
      $config = new \Packaged\Config\Provider\ConfigSection(
        'http_visitor',
        ['database' => $this->_geoipdb]
      );
    }

    $config->addItem('database', $config->getItem('database', $this->_geoipdb));

    $cubex->getConfiguration()->addSection($config);

    $visitor = new \Cubex\Http\Visitor\MaxmindVisitor();
    $visitor->configure($config);
    $visitor->setClientIp($remoteAddr);
    $this->assertEquals($country, $visitor->getCountry());
    $this->assertEquals($city, $visitor->getCity());
    $this->assertEquals($region, $visitor->getRegion());
  }

  public function testNoConfig()
  {
    $this->setExpectedException(
      "Exception",
      "You must configure the MaxMindVisitor class"
    );
    $visitor = new \Cubex\Http\Visitor\MaxmindVisitor();
    $visitor->getCountry();
  }

  public function testNoLicence()
  {
    $this->setExpectedException(
      "Exception",
      "No maxmind licence key specified"
    );
    $visitor = new \Cubex\Http\Visitor\MaxmindVisitor();
    $visitor->configure(
      new \Packaged\Config\Provider\ConfigSection(
        'http_visitor',
        [
          'mode'    => 'client',
          'user_id' => '',
        ]
      )
    );
    $visitor->getCountry();
  }

  public function testNoUserId()
  {
    $this->setExpectedException(
      "Exception",
      "No maxmind user id specified"
    );
    $visitor = new \Cubex\Http\Visitor\MaxmindVisitor();
    $visitor->configure(
      new \Packaged\Config\Provider\ConfigSection(
        'http_visitor',
        [
          'mode'        => 'client',
          'licence_key' => '',
        ]
      )
    );
    $visitor->getCountry();
  }

  public function testClientCreates()
  {
    $visitor = new \Cubex\Http\Visitor\MaxmindVisitor();
    $visitor->configure(
      new \Packaged\Config\Provider\ConfigSection(
        'http_visitor',
        [
          'mode'        => 'client',
          'licence_key' => '',
          'user_id'     => '',
        ]
      )
    );
    $visitor->getCountry();
  }

  public function visitorProvider()
  {
    return [
      [
        '123.123.123.123',
        'CN',
        'Beijing',
        '11',
        new \Packaged\Config\Provider\ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => $this->_geoipdb
          ]
        )
      ],
      [
        '208.67.222.222',
        'US',
        'San Francisco',
        'CA',
        new \Packaged\Config\Provider\ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => $this->_geoipdb
          ]
        )
      ],
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
      ]
    ];
  }
}
