<?php

namespace Baselib\Cache;

/**
 * @desc
 * @package   Baselib
 * @author    Barry Tang <20962493@qq.com>
 * @created   2018/06/19 09:56
 * @copyright GPLv3
 */

interface CacheInterfaceEx{
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
    public function inc($key, $inc = 1, $ttl = null);

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
    public function dec($key, $dec = 1, $ttl = null);
}