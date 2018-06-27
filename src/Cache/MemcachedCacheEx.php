<?php

namespace Baselib\Cache;

/**
 * @desc
 * @package   Baselib
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright GPLv3
 */

class MemcachedCacheEx extends \Symfony\Component\Cache\Simple\MemcachedCache implements CacheInterfaceEx{
    public static function createConnectionEx($dsn, $namespace = '', $defaultLifeTime = 0) {
        return new MemcachedCacheEx(\Symfony\Component\Cache\Simple\MemcachedCache::createConnection($dsn), $namespace, $defaultLifeTime);
    }
    protected function doInc($key, $inc, $lifetime)
    {
        if ($lifetime && $lifetime > 30 * 86400) {
            $lifetime += time();
        }
        return $this->checkResultCode($this->getClient()->increment($key, $inc, 0, $lifetime));
    }

    protected function doDec($key, $dec, $lifetime)
    {
        if ($lifetime && $lifetime > 30 * 86400) {
            $lifetime += time();
        }
        return $this->checkResultCode($this->getClient()->decrement($key, $dec, 0, $lifetime));
    }

    /**
     * Increase a value in cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param integer $inc    Increase value.
     * @param integer $ttl    Ttl value.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function inc($key, $inc = 1, $ttl = null) {
        $key = $this->getId($key);
        if (false === $ttl = $this->normalizeTtl($ttl)) {
            return $this->doDelete(array($key));
        }
        try {
            $e = $this->doInc($key, $inc, $ttl);
        } catch (\Exception $e) {
        }
        if (true === $e || array() === $e) {
            return true;
        }
        return false;
    }

    /**
     * Decrease a value in cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param integer $inc    Decrease value.
     * @param integer $ttl    Ttl value.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function dec($key, $dec = 1, $ttl = null) {
        $key = $this->getId($key);
        if (false === $ttl = $this->normalizeTtl($ttl)) {
            return $this->doDelete(array($key));
        }
        try {
            $e = $this->doDec($key, $dec, $ttl);
        } catch (\Exception $e) {
        }
        if (true === $e || array() === $e) {
            return true;
        }
        return false;
    }
}