<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CacheCounterTest extends TestCase {
    public function testCacheCounter() {
        $dsn = 'memcached://127.0.0.1:18080';
        Baselib\Utils::asyncExec('timeout 5 memcached -p 18080 -m 20 -u root -l 127.0.0.1');
        sleep(1);
        $client = \Symfony\Component\Cache\Simple\MemcachedCache::createConnection($dsn);
        $client->set('aaaa',1);
        var_dump($client->get('aaaa'));
        $cache = Baselib\Utils::getCacheInstance($dsn, 'test');
        $counter = new Baselib\CacheCounter($cache);
        $counter->inc('aaa');
        $counter->inc('aaa');
        var_dump($counter->get('aaa'));
    }
}
