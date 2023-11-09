<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Cache\Adapter;

use ArrayAccess\DnsRecord\Cache\CacheData;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheAdapterInterface;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheDataInterface;

/**
 * Void cache object is for uncached data
 */
final class VoidCacheAdapter implements CacheAdapterInterface
{
    /**
     * @inheritdoc
     */
    public function saveItem(CacheDataInterface $cacheData): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteItem(string $key): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteItems(string ...$keys): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getItem(string $key): CacheDataInterface
    {
        return new CacheData($key);
    }

    /**
     * @inheritdoc
     */
    public function hasItem(string $key): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function clear(): bool
    {
        return true;
    }
}
