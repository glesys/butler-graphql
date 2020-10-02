<?php

namespace Butler\Graphql\Tests;

use Butler\Graphql\CacheMap;
use stdClass;

class CacheMapTest extends AbstractTestCase
{
    public function test_count_and_clear()
    {
        $cacheMap = new CacheMap();

        $this->assertSame(0, $cacheMap->count());

        $cacheMap->set('foo', 'bar');
        $cacheMap->set('hello', 'world');

        $this->assertSame(2, $cacheMap->count());

        $cacheMap->clear();

        $this->assertSame(0, $cacheMap->count());
        $this->assertFalse($cacheMap->get('foo'));
        $this->assertFalse($cacheMap->get('bar'));
    }

    public function test_delete()
    {
        $cacheMap = new CacheMap();

        $cacheMap->set('foo', 'bar');
        $cacheMap->set('hello', 'world');

        $cacheMap->delete('foo');

        $this->assertFalse($cacheMap->get('foo'));
        $this->assertSame('world', $cacheMap->get('hello'));
    }

    public function test_keys()
    {
        $cacheMap = new CacheMap();

        $cacheMap->set(1, 'integer');
        $cacheMap->set(2.3, 'float');
        $cacheMap->set('foo', 'string');

        $cacheMap->set(['foo' => 'bar'], 'associative array');
        $cacheMap->set([1, 2, 3], 'indexed array');
        $cacheMap->set([], 'empty array');

        $cacheMap->set($object1 = tap(new stdClass(), function ($o) { $o->prop = 'foo'; }), 'object 1');
        $cacheMap->set($object2 = tap(new stdClass(), function ($o) { $o->prop = 'bar'; }), 'object 2');

        $this->assertSame('integer', $cacheMap->get(1));
        $this->assertSame('float', $cacheMap->get(2.3));
        $this->assertSame('string', $cacheMap->get('foo'));

        $this->assertSame('associative array', $cacheMap->get(['foo' => 'bar']));
        $this->assertSame('indexed array', $cacheMap->get([1, 2, 3]));
        $this->assertSame('empty array', $cacheMap->get([]));

        $this->assertSame('object 1', $cacheMap->get($object1));
        $this->assertSame('object 2', $cacheMap->get($object2));
    }
}
