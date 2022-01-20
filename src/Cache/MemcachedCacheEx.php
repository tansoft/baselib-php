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
    protected $globalClient;
    protected $globalNameSpace;
    protected $globalLifeTime;
    public static function createConnectionEx($dsn, $namespace = '', $defaultLifeTime = 0) {
        return new MemcachedCacheEx(\Symfony\Component\Cache\Simple\MemcachedCache::createConnection($dsn), $namespace, $defaultLifeTime);
    }
    public function __construct(\Memcached $client, string $namespace = '', int $defaultLifetime = 0)
    {
        $this->globalClient = $client;
        $this->globalNameSpace = $namespace;
        $this->globalLifeTime = $defaultLifetime;
        parent::__construct($client, $namespace, $defaultLifetime);
    }
    protected function doInc($key, $inc, $lifetime)
    {
        if ($lifetime && $lifetime > 30 * 86400) {
            $lifetime += time();
        }
        return $this->checkResultCode2($this->globalClient->increment($key, $inc, 0, $lifetime));
    }

    protected function doDec($key, $dec, $lifetime)
    {
        if ($lifetime && $lifetime > 30 * 86400) {
            $lifetime += time();
        }
        return $this->checkResultCode2($this->globalClient->decrement($key, $dec, 0, $lifetime));
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
        $key = $this->getId2($key);
        if (false === $ttl = $this->normalizeTtl2($ttl)) {
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
        $key = $this->getId2($key);
        if (false === $ttl = $this->normalizeTtl2($ttl)) {
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

    private function getId2($key)
    {
        return $this->globalNameSpace.$key;
    }

    private function normalizeTtl2($ttl)
    {
        if (null === $ttl) {
            return $this->globalLifeTime;
        }
        if ($ttl instanceof \DateInterval) {
            $ttl = (int) \DateTime::createFromFormat('U', 0)->add($ttl)->format('U');
        }
        if (\is_int($ttl)) {
            return 0 < $ttl ? $ttl : false;
        }

        throw new InvalidArgumentException(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given', is_object($ttl) ? get_class($ttl) : gettype($ttl)));
    }

    private function checkResultCode2($result)
    {
        $code = $this->client->getResultCode();

        if (\Memcached::RES_SUCCESS === $code || \Memcached::RES_NOTFOUND === $code) {
            return $result;
        }

        throw new CacheException(sprintf('MemcachedAdapter client error: %s.', strtolower($this->client->getResultMessage())));
    }
}