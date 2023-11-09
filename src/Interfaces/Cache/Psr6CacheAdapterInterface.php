<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Psr6 Cache implementation Wrapper
 * @link https://www.php-fig.org/psr/psr-6/
 */
interface Psr6CacheAdapterInterface extends CacheAdapterInterface
{
    /**
     * Get object cache item pool
     *
     * @return ?CacheItemPoolInterface null if not available
     */
    public function getCacheItemPool() : ?CacheItemPoolInterface;

    /**
     * Get item from packet
     * Implementation of cache request
     *
     * @param string $key
     * @return ?CacheItemInterface
     */
    public function getCacheItem(string $key) : ?CacheItemInterface;
}
