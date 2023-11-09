<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Interfaces\Cache;

use DateInterval;
use DateTimeInterface;
use Serializable;

/**
 * Cache storage just like psr6 cache implementation
 */
interface CacheDataInterface extends Serializable
{
    /**
     * Get key for cache
     *
     * @return string
     */
    public function getKey() : string;

    /**
     * Get cached data
     *
     * @return mixed real data
     */
    public function get(): mixed;

    /**
     * Set cache data
     *
     * @param mixed $data
     * @return $this
     */
    public function set(mixed $data): static;

    /**
     * Get ttl or expires after in integer
     *
     * @return ?int
     */
    public function getExpiresAfter() : ?int;

    /**
     * Set cache expire after
     *
     * @param int|DateInterval|null $time
     * @return $this
     */
    public function expiresAfter(int|DateInterval|null $time): static;

    /**
     * Set expired at from given date
     *
     * @param ?DateTimeInterface $expiration
     * @return $this
     */
    public function expiresAt(?DateTimeInterface $expiration): static;
}
