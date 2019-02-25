<?php

namespace Cubex\Tests\Container;

use Cubex\Container\DependencyInjector;
use Cubex\Tests\Supporting\Container\TestObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DependencyInjectorTest extends TestCase
{
  /**
   * @throws \Exception
   */
  public function testShare()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->hasShared('S'));
    $this->assertFalse($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', true));
    $di->share('S', null);
    $this->assertFalse($di->hasShared('S'));
    $class = new stdClass();
    $di->share('S', $class);
    $this->assertTrue($di->hasShared('S'));
    $this->assertTrue($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', false));
    $this->assertSame($class, $di->retrieve('S'));
    $di->removeShared('S');
    $this->assertFalse($di->hasShared('S'));
  }

  /**
   * @throws \Exception
   */
  public function testShareImmutable()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->hasShared('S'));
    $this->assertFalse($di->hasShared('S', DependencyInjector::MODE_IMMUTABLE));
    $this->assertFalse($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', true));
    $di->share('S', null);
    $this->assertFalse($di->hasShared('S'));
    $class = new stdClass();
    $class->x = 'y';
    $di->share('S', $class, DependencyInjector::MODE_IMMUTABLE);
    $this->assertTrue($di->hasShared('S'));
    $this->assertTrue($di->hasShared('S', DependencyInjector::MODE_IMMUTABLE));
    $this->assertFalse($di->hasShared('S', DependencyInjector::MODE_MUTABLE));
    $this->assertTrue($di->isAvailable('S'));
    $this->assertFalse($di->isAvailable('S', false));
    $this->assertSame($class, $di->retrieve('S'));
    $di->removeShared('S');
    $this->assertSame($class, $di->retrieve('S'));
    $this->assertTrue($di->hasShared('S'));
    $this->assertTrue($di->hasShared('S', DependencyInjector::MODE_IMMUTABLE));
    $class2 = new stdClass();
    $class2->x = 'z';
    $di->share('S', $class2, DependencyInjector::MODE_IMMUTABLE);
    $this->assertSame($class, $di->retrieve('S'));
  }

  /**
   * @throws \Exception
   */
  public function testFactory()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->isAvailable('F'));
    $this->assertFalse($di->isAvailable('F', false));
    $this->assertFalse($di->isAvailable('F', true));
    $di->factory(
      'F',
      function (...$params) {
        $instance = new TestObject($params);
        return $instance;
      }
    );
    $this->assertTrue($di->isAvailable('F'));
    $this->assertTrue($di->isAvailable('F', false));
    $this->assertTrue($di->isAvailable('F', true));
    $this->assertFalse($di->hasShared('F'));

    /** @var TestObject $i */
    $i = $di->retrieve('F', ['one', 'two'], false);
    $this->assertInstanceOf(TestObject::class, $i);
    $this->assertEquals(2, $i->paramCount());
    $this->assertFalse($di->hasShared('F'));
    /** @var TestObject $i2 */
    $i2 = $di->retrieve('F', ['a', 'b', 'c'], true);
    $this->assertEquals(3, $i2->paramCount());
    $this->assertTrue($di->hasShared('F'));
    $this->assertTrue($di->hasShared('F', DependencyInjector::MODE_MUTABLE));

    $i3 = $di->retrieve('F');
    $this->assertSame($i2, $i3);
  }

  /**
   * @throws \Exception
   */
  public function testImmutableFactory()
  {
    $di = new DependencyInjector();
    $this->assertFalse($di->isAvailable('F'));
    $this->assertFalse($di->isAvailable('F', false));
    $this->assertFalse($di->isAvailable('F', true));
    $di->factory(
      'F',
      function (...$params) {
        $instance = new TestObject($params);
        return $instance;
      },
      DependencyInjector::MODE_IMMUTABLE
    );
    $this->assertTrue($di->isAvailable('F'));
    $this->assertTrue($di->isAvailable('F', false));
    $this->assertTrue($di->isAvailable('F', true));
    $this->assertFalse($di->hasShared('F'));

    /** @var TestObject $i */
    $i = $di->retrieve('F', ['one', 'two']);
    $this->assertInstanceOf(TestObject::class, $i);
    $this->assertEquals(2, $i->paramCount());
    $this->assertTrue($di->hasShared('F', DependencyInjector::MODE_IMMUTABLE));
    $this->assertFalse($di->hasShared('F', DependencyInjector::MODE_MUTABLE));
    $this->assertTrue($di->hasShared('F'));
    /** @var TestObject $i2 */
    $i2 = $di->retrieve('F', ['a', 'b', 'c']);
    $this->assertEquals(2, $i2->paramCount());
    $this->assertTrue($di->hasShared('F'));
    $this->assertSame($i, $i2);

    $i3 = $di->retrieve('F', ['a', 'z', 'x', 'y'], false);
    $this->assertEquals(4, $i3->paramCount());
    $this->assertNotSame($i2, $i3);
  }
}
