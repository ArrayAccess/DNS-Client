<?php
declare(strict_types=1);

namespace ArrayAccess\DnsRecord\Cache;

use ArrayAccess\DnsRecord\Interfaces\Cache\CacheAdapterInterface;
use ArrayAccess\DnsRecord\Interfaces\Cache\CacheStorageInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketRequestDataInterface;
use ArrayAccess\DnsRecord\Interfaces\Packet\PacketResponseInterface;
use DateInterval;
use function array_map;
use function array_values;
use function is_object;
use function sprintf;

class CacheStorage implements CacheStorageInterface
{
    /**
     * Cache adapter object
     *
     * @var ?CacheAdapterInterface
     */
    private ?CacheAdapterInterface $adapter;

    public function __construct(?CacheAdapterInterface $adapter = null)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     */
    public function getAdapter(): ?CacheAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @inheritdoc
     */
    public function setAdapter(CacheAdapterInterface $cacheAdapter): void
    {
        $this->adapter = $cacheAdapter;
    }

    /**
     * @inheritdoc
     */
    public function saveItem(PacketResponseInterface $response, int|DateInterval|null $ttl = self::DEFAULT_TTL): bool
    {
        $cacheItem = $this->getAdapter()->getItem($this->getCacheName($response->getPacketData()));
        $cacheItem
            ->set($response)
            ->expiresAfter($ttl??self::DEFAULT_TTL);
        return $this->getAdapter()?->saveItem(
            $cacheItem
        );
    }

    /**
     * @inheritdoc
     */
    public function hasItem(PacketRequestDataInterface|string $key): bool
    {
        return $this->getAdapter()?->hasItem($this->getCacheName($key))?:false;
    }

    /**
     * @inheritdoc
     */
    public function getItem(PacketRequestDataInterface|string $key): ?PacketResponseInterface
    {
        $key = $this->getCacheName($key);
        if (!$this->getAdapter()?->hasItem($key)) {
            return null;
        }
        $cacheData = $this->getAdapter()->getItem($key)->get();
        return $cacheData instanceof PacketResponseInterface
            ? $cacheData
            : null;
    }

    /**
     * @inheritdoc
     */
    public function deleteItem(PacketRequestDataInterface|string $key): bool
    {
        return $this->getAdapter()?->deleteItem($this->getCacheName($key));
    }

    /**
     * @inheritdoc
     */
    public function deleteItems(string|PacketRequestDataInterface ...$keys): bool
    {
        return $this->getAdapter()?->deleteItems(
            ...array_values(array_map([$this, 'getCacheName'], $keys))
        );
    }

    /**
     * @inheritdoc
     */
    public function getCacheName(PacketRequestDataInterface|string $key): string
    {
        return is_object($key)
            ? sprintf('php_dns_client_%s', md5($key->getQueryMessage()))
            : $key;
    }
}