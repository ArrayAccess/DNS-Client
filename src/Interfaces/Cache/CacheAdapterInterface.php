<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Cache;

/**
 * Dns Client cache adapter.
 * Adapted psr6 cache implementation
 */
interface CacheAdapterInterface
{
//    /**
//     * Instance constructor
//     *
//     * @param CacheStorageInterface $cacheStorage
//     */
//    public function __construct(CacheStorageInterface $cacheStorage);
//
//    /**
//     * Get a cache storage object from instance
//     *
//     * @return CacheStorageInterface
//     */
//    public function getCacheStorage(): CacheStorageInterface;

    /**
     * Save the cache item
     *
     * @param CacheDataInterface $cacheData
     */
    public function saveItem(CacheDataInterface $cacheData) : bool;

    /**
     * Determine the object storage contains cache by given key
     *
     * @param string $key
     * @return bool
     */
    public function hasItem(string $key) : bool;

    /**
     * Delete cache by key
     *
     * @param string $key
     */
    public function deleteItem(string $key) : bool;

    /**
     * Delete cache by given keys
     *
     * @param string ...$keys
     */
    public function deleteItems(string ...$keys) : bool;

    /**
     * Get cache item, even does not exist create an empty object
     *
     * @param string $key
     */
    public function getItem(string $key) : CacheDataInterface;

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear(): bool;
}
