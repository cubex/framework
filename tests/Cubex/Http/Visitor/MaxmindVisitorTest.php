<?php
namespace CubexTest\Cubex\Http\Visitor;

use Cubex\Http\Request;
use Cubex\Http\Visitor\MaxmindVisitor;
use CubexTest\InternalCubexTestCase;
use Packaged\Config\Provider\ConfigSection;

class MaxmindVisitorTestInternal extends InternalCubexTestCase
{
  protected $_geoipdbFilename = __DIR__ . '/GeoIP2-City-Test.mmdb';

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
    if(!file_exists($this->_geoipdbFilename))
    {
      $this->markTestSkipped("GeoIP Database Not Downloaded");
      return;
    }

    $cubex = $this->newCubexInstace();
    $request = new Request();
    $server = ['REMOTE_ADDR' => $remoteAddr];
    $request->initialize([], [], [], [], [], $server);
    $cubex->instance('request', $request);

    if($config === null)
    {
      $config = new ConfigSection(
        'http_visitor',
        ['database' => $this->_geoipdbFilename]
      );
    }

    $config->addItem('database', $config->getItem('database', $this->_geoipdbFilename));

    $cubex->getConfiguration()->addSection($config);

    $visitor = new MaxmindVisitor();
    $visitor->configure($config);
    $visitor->setClientIp($remoteAddr);
    $this->assertEquals($country, $visitor->getCountry());
    $this->assertEquals($city, $visitor->getCity());
    $this->assertEquals($region, $visitor->getRegion());
  }

  public function testNoConfig()
  {
    $this->expectException(
      "Exception",
      "You must configure the MaxMindVisitor class"
    );
    $visitor = new MaxmindVisitor();
    $visitor->getCountry();
  }

  public function testNoLicence()
  {
    $this->expectException(
      "Exception",
      "No maxmind licence key specified"
    );
    $visitor = new MaxmindVisitor();
    $visitor->configure(
      new ConfigSection(
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
    $this->expectException(
      "Exception",
      "No maxmind user id specified"
    );
    $visitor = new MaxmindVisitor();
    $visitor->configure(
      new ConfigSection(
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
    $visitor = new MaxmindVisitor();
    $visitor->configure(
      new ConfigSection(
        'http_visitor',
        [
          'mode'        => 'client',
          'licence_key' => '',
          'user_id'     => '',
        ]
      )
    );
    $visitor->getCountry();
    $this->assertEquals("GB", $visitor->getCountry());
  }

  public function visitorProvider()
  {
    return [
      [
        '81.2.69.192',
        'GB',
        'London',
        'ENG',
        new ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => $this->_geoipdbFilename,
          ]
        ),
      ],
      [
        '216.160.83.56',
        'US',
        'Milton',
        'WA',
        new ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => $this->_geoipdbFilename,
          ]
        ),
      ],
      [
        '89.160.20.112',
        'SE',
        'Linköping',
        'E',
        new ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => $this->_geoipdbFilename,
          ]
        ),
      ],
      ['127.0.0.1', 'GB', 'London', 'eng'],
      [
        '127.0.0.1',
        'UK',
        'Portsmouth',
        'england',
        new ConfigSection(
          'http_visitor',
          ['city' => 'Portsmouth', 'country' => 'UK', 'region' => 'england']
        ),
      ],
    ];
  }
}
