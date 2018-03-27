<?php
namespace CubexTest\Cubex\Http\Visitor;

use Cubex\Http\Request;
use Cubex\Http\Visitor\MaxmindVisitor;
use CubexTest\InternalCubexTestCase;
use Packaged\Config\Provider\ConfigSection;
use Packaged\Helpers\Path;

class MaxmindVisitorTestInternal extends InternalCubexTestCase
{
  protected static $_geoipdbFilename;

  public static function setUpBeforeClass()
  {
    $dbgz = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz';

    $gzFilename = Path::build(sys_get_temp_dir(), 'GeoLite2-City.tar.gz');
    $tarFilename = substr($gzFilename, 0, -3);
    $extractLocation = substr($tarFilename, 0, -4);

    $opts = [
      'http' => [
        'method' => "GET",
        'header' => "Accept-language: en\r\n"
          . "User-Agent: CURL (Cubex Framework; en-us)\r\n",
      ],
    ];

    $context = stream_context_create($opts);

    file_put_contents($gzFilename, file_get_contents($dbgz, false, $context));

    if(file_exists($tarFilename))
    {
      unlink($tarFilename);
    }

    $phar = new \PharData($gzFilename);
    $phar->decompress();
    $tar = new \PharData($tarFilename);
    $tar->extractTo($extractLocation, null, true);
    if($tar->current()->isDir())
    {
      $extractLocation .= '/' . basename($phar->current()->getPathname());
    }

    static::$_geoipdbFilename .= $extractLocation . '/GeoLite2-City.mmdb';
    unlink($gzFilename);
    unlink($tarFilename);
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
    if(!file_exists(static::$_geoipdbFilename))
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
        ['database' => static::$_geoipdbFilename]
      );
    }

    $config->addItem('database', $config->getItem('database', static::$_geoipdbFilename));

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
        '123.123.123.123',
        'CN',
        'Beijing',
        'BJ',
        new ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => static::$_geoipdbFilename,
          ]
        ),
      ],
      [
        '208.67.222.222',
        'US',
        'San Francisco',
        'CA',
        new ConfigSection(
          'http_visitor',
          [
            'mode'     => 'reader',
            'database' => static::$_geoipdbFilename,
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
