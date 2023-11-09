<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Cache;

use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use DateInterval;

interface CacheStorageInterface
{
    /**
     * Default time to live of cache stored
     */
    const DEFAULT_TTL = 300;

    /**
     * Maximum DNS Cache time
     */
    const MAXIMUM_TTL = 1800;

    /**
     * Get cache adapter
     *
     * @return ?CacheAdapterInterface
     */
    public function getAdapter() : ?CacheAdapterInterface;

    /**
     * Set cache adapter
     *
     * @param CacheAdapterInterface $cacheAdapter
     */
    public function setAdapter(CacheAdapterInterface $cacheAdapter);

    /**
     * Store the packet response into cache
     *
     * @param PacketResponseInterface $response
     * @param int|DateInterval|null $ttl
     */
    public function saveItem(PacketResponseInterface $response, int|DateInterval|null $ttl = self::DEFAULT_TTL) : bool;

    /**
     * Check if cache exists
     *
     * @param PacketRequestDataInterface|string $key
     * @return bool
     */
    public function hasItem(PacketRequestDataInterface|string $key) : bool;

    /**
     * Delete the object from given key
     *
     * @param PacketRequestDataInterface|string $key
     * @return bool
     */
    public function deleteItem(PacketRequestDataInterface|string $key) : bool;

    /**
     * Delete items from given keys
     *
     * @param PacketRequestDataInterface|string ...$keys
     * @return bool
     */
    public function deleteItems(PacketRequestDataInterface|string ...$keys) : bool;

    /**
     * Get object PacketResponseInterface from given request
     *
     * @param PacketRequestDataInterface|string $key
     * @return ?PacketResponseInterface
     */
    public function getItem(PacketRequestDataInterface|string $key) : ?PacketResponseInterface;

    /**
     * Get cache name from given request object
     *
     * @param PacketRequestDataInterface|string $key
     * @return string
     */
    public function getCacheName(PacketRequestDataInterface|string $key) : string;
}
