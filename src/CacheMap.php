<?php

namespace Butler\Graphql;

use Countable;
use leinonen\DataLoader\CacheMapInterface;

class CacheMap implements CacheMapInterface, Countable
{
    private $cache = [];

    public function get($key)
    {
        $key = $this->serializeKey($key);

        return array_key_exists($key, $this->cache)
            ? $this->cache[$key]
            : false;
    }

    public function set($key, $value)
    {
        $this->cache[self::serializeKey($key)] = $value;
    }

    public function delete($key)
    {
        unset($this->cache[self::serializeKey($key)]);
    }

    public function clear()
    {
        $this->cache = [];
    }

    public function count()
    {
        return count($this->cache);
    }

    private static function serializeKey($key)
    {
        if (is_object($key)) {
            return spl_object_hash($key);
        } elseif (is_array($key)) {
            return md5(json_encode($key));
        }
        return (string) $key;
    }
}
