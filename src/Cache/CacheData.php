<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Cache;

use ArrayAccess\DnsRecord\Exceptions\CacheException;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheDataInterface;
use DateInterval;
use DateTimeInterface;
use function is_array;
use function is_string;
use function serialize;
use function unserialize;

/**
 * Cache data storage just like a psr6 cache item
 * @see \Psr\Cache\CacheItemInterface
 */
class CacheData implements CacheDataInterface
{
    /**
     * The cache key
     *
     * @var string
     */
    private string $key;

    /**
     * The cache data, default null
     *
     * @var mixed
     */
    private mixed $data;

    /**
     * Cache time to live
     *
     * @var ?int
     */
    private ?int $ttl = null;

    /**
     * Construct an added key
     *
     * @param string $key
     * @param mixed $value
     */
    public function __construct(string $key, mixed $value = null)
    {
        $this->key = $key;
        $this->data = $value;
    }

    /**
     * @inheritdoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritdoc
     */
    public function get(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function set(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getExpiresAfter(): ?int
    {
        return $this->ttl;
    }

    /**
     * @inheritdoc
     */
    public function expiresAfter(DateInterval|int|null $time): static
    {
        if ($time instanceof DateInterval) {
            $time = $time->days * 86400
                + $time->h * 3600
                + $time->i * 60
                + $time->s;
        }
        $this->ttl = $time;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function expiresAt(?DateTimeInterface $expiration): static
    {
        if ($expiration !== null) {
            $expiration = time() - $expiration->getTimestamp();
        }

        return $this->expiresAfter($expiration);
    }

    /**
     * @inheritdoc
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     * @throws CacheException
     */
    public function unserialize(string $data): void
    {
        $data = unserialize($data);
        if (!is_array($data) || !is_string($data['key']??null)) {
            throw new CacheException(
                'Invalid serialized data'
            );
        }
        $this->__unserialize($data);
    }

    /**
     * Magic method for unserialize
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->key = $data['key'];
        $this->expiresAfter($data['ttl']);
        $this->set($data['data']);
    }

    /**
     * Magic method for serializing
     *
     * @return array{key: string, data: mixed, ttl:int}
     */
    public function __serialize(): array
    {
        return [
            'key' => $this->key,
            'data' => $this->data,
            'ttl' => $this->ttl,
        ];
    }
}
