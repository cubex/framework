<?php

namespace Cubex\Tests\Cache;

use Cubex\Cache\EphemeralCache;
use PHPUnit\Framework\TestCase;

class EphemeralCacheTest extends TestCase
{
  public function testCache()
  {
    $cache = new EphemeralCache();
    $cache2 = new EphemeralCache();
    self::assertFalse($cache->has('test'));
    self::assertEquals('value', $cache->retrieve('test', function () { return 'value'; }, 1));
    self::assertEquals('value', $cache->retrieve('test', function () { return 'value2'; }, 1));
    self::assertFalse($cache2->has('test'));//Check cache is not static
    self::assertTrue($cache->has('test'));
    self::assertEquals('value', $cache->get('test'));

    //Check TTL
    sleep(2);
    self::assertFalse($cache->has('test'));
  }

  public function testCacheInstance()
  {
    $cache1 = EphemeralCache::instance();
    $cache1->set('test', 'abc');
    self::assertEquals('abc', $cache1->get('test', 'def'));
    $cache2 = EphemeralCache::instance();
    self::assertEquals('abc', $cache2->get('test', 'def'));

    $cache2->delete('test');
    self::assertEquals('def', $cache1->get('test', 'def'));
  }
}
