<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Packet;

use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;

interface PacketRequestInterface
{
    const DEFAULT_TIMEOUT = 5;

    const MAX_TIMEOUT = 60;

    /**
     * Request constructor
     *
     * @param PacketRequestDataInterface $packetData
     * @return PacketResponseInterface
     */
    public function __construct(PacketRequestDataInterface $packetData);

    /**
     * Get a cache storage object
     *
     * @return ?CacheStorageInterface
     */
    public function getCache(): ?CacheStorageInterface;

    /**
     * Set cache storage object
     *
     * @param ?CacheStorageInterface $cache
     * @return static
     */
    public function setCache(?CacheStorageInterface $cache) : static;

    /**
     * Get packet data
     *
     * @return PacketRequestDataInterface
     */
    public function getPacketData(): PacketRequestDataInterface;

    /**
     * Get packet response
     *
     * @return PacketResponseInterface
     */
    public function getResponse() : PacketResponseInterface;

    /**
     * Send packet request
     *
     * @param int|float $timeout
     * @return PacketResponseInterface
     */
    public function send(int|float $timeout = self::DEFAULT_TIMEOUT) : PacketResponseInterface;
}
